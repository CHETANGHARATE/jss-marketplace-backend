<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Settlement;
use App\Models\VendorStore;
use App\Models\VendorTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class VendorCommissionService
{
    /**
     * Calculate and credit vendor wallet balances for an order upon payment.
     */
    public function processOrderCommission(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $groupedItems = $order->items->groupBy('seller_id');

            foreach ($groupedItems as $sellerId => $items) {
                if (!$sellerId) {
                    continue;
                }

                $store = VendorStore::where('user_id', $sellerId)->with('wallet')->first();
                if (!$store || !$store->wallet) {
                    continue;
                }

                $sellerSubtotal = (float) $items->sum('subtotal');
                $commissionRate = (float) $store->commission_rate;
                $commissionAmount = round(($sellerSubtotal * $commissionRate) / 100, 2);
                $netCredit = $sellerSubtotal - $commissionAmount;

                // Credit Wallet
                $store->wallet->increment('balance', $netCredit);

                // Log Transaction
                VendorTransaction::create([
                    'vendor_wallet_id' => $store->wallet->id,
                    'order_id' => $order->id,
                    'type' => 'credit',
                    'amount' => $netCredit,
                    'commission_amount' => $commissionAmount,
                    'description' => "Order #{$order->order_number} payout (Subtotal: {$sellerSubtotal}, Fee: {$commissionAmount})",
                ]);
            }
        });
    }

    /**
     * Vendor request payout settlement.
     */
    public function requestSettlement(VendorStore $store, float $amount, array $bankDetails): Settlement
    {
        $wallet = $store->wallet;

        if (!$wallet || $wallet->balance < $amount) {
            throw new Exception("Insufficient wallet balance for settlement request.");
        }

        return DB::transaction(function () use ($store, $wallet, $amount, $bankDetails) {
            // Deduct balance
            $wallet->decrement('balance', $amount);

            $settlementNumber = 'SET-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $settlement = Settlement::create([
                'settlement_number' => $settlementNumber,
                'vendor_store_id' => $store->id,
                'amount' => $amount,
                'bank_details' => $bankDetails,
                'status' => 'requested',
            ]);

            // Log Transaction
            VendorTransaction::create([
                'vendor_wallet_id' => $wallet->id,
                'order_id' => null,
                'type' => 'payout',
                'amount' => $amount,
                'commission_amount' => 0.00,
                'description' => "Settlement payout requested #{$settlementNumber}",
            ]);

            return $settlement;
        });
    }

    /**
     * Process settlement status (Admin).
     */
    public function processSettlement(Settlement $settlement, string $status, ?string $referenceNumber = null): Settlement
    {
        return DB::transaction(function () use ($settlement, $status, $referenceNumber) {
            if ($status === 'rejected' && $settlement->status !== 'rejected') {
                // Refund wallet balance if rejected
                $settlement->store->wallet->increment('balance', $settlement->amount);
            }

            if ($status === 'paid') {
                $settlement->store->wallet->increment('total_withdrawn', $settlement->amount);
            }

            $settlement->update([
                'status' => $status,
                'reference_number' => $referenceNumber,
                'processed_at' => now(),
            ]);

            return $settlement->fresh();
        });
    }
}

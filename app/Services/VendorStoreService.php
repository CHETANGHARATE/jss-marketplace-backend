<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\VendorStore;
use App\Models\VendorWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class VendorStoreService
{
    /**
     * Register a new vendor store profile.
     */
    public function registerStore(User $user, array $data): VendorStore
    {
        if (VendorStore::where('user_id', $user->id)->exists()) {
            throw new Exception("You already have a vendor store registered.");
        }

        return DB::transaction(function () use ($user, $data) {
            // Assign seller role
            if (!$user->hasRole(UserRole::SELLER->value)) {
                $user->assignRole(UserRole::SELLER->value);
            }

            $slug = Str::slug($data['store_name']) . '-' . Str::random(4);

            $store = VendorStore::create([
                'user_id' => $user->id,
                'store_name' => $data['store_name'],
                'slug' => $slug,
                'store_email' => $data['store_email'] ?? $user->email,
                'store_phone' => $data['store_phone'] ?? $user->phone,
                'description' => $data['description'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'kyc_status' => 'pending',
                'kyc_documents' => $data['kyc_documents'] ?? null,
                'status' => 'pending',
                'commission_rate' => 10.00, // Default 10%
            ]);

            // Initialize Wallet
            VendorWallet::create([
                'vendor_store_id' => $store->id,
                'balance' => 0.00,
                'pending_balance' => 0.00,
                'total_withdrawn' => 0.00,
            ]);

            return $store->fresh(['wallet']);
        });
    }

    /**
     * Verify Vendor KYC & Activate Store (Admin).
     */
    public function verifyKYC(VendorStore $store, string $kycStatus): VendorStore
    {
        return DB::transaction(function () use ($store, $kycStatus) {
            $storeStatus = $kycStatus === 'verified' ? 'active' : 'pending';

            $store->update([
                'kyc_status' => $kycStatus,
                'status' => $storeStatus,
            ]);

            return $store->fresh();
        });
    }
}

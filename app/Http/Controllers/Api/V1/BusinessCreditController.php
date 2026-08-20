<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BusinessCreditAccount;
use App\Models\BusinessCreditTransaction;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessCreditController extends Controller
{
    /**
     * Get authenticated buyer's Business Credit Account & Ledger (Feature 94).
     */
    public function getAccount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $account = BusinessCreditAccount::where('user_id', $userId)
            ->with(['transactions' => function ($q) {
                $q->latest()->take(20);
            }])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $account,
        ], 200);
    }

    /**
     * Buyer: Apply for Business Credit / Pay-Later Limit (Feature 94).
     */
    public function apply(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'requested_limit' => 'required|numeric|min:1000',
            'business_turnover' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $account = BusinessCreditAccount::updateOrCreate(
            ['user_id' => $userId],
            [
                'status' => 'pending',
                'admin_notes' => 'Application submitted: Requested limit ₹' . number_format($validated['requested_limit'], 2) . '. Notes: ' . ($validated['notes'] ?? 'None'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Business credit application submitted. Our credit underwriting team will review and assign your credit limit.',
            'data' => $account,
        ], 200);
    }

    /**
     * Admin: List all Business Credit Accounts.
     */
    public function adminList(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $query = BusinessCreditAccount::with('user.businessAccount')->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $accounts = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $accounts->items(),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'total' => $accounts->total(),
            ],
        ], 200);
    }

    /**
     * Admin: Approve or adjust business credit limit (Feature 94).
     */
    public function adminApproveLimit(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'repayment_due_days' => 'required|integer|min:7|max:180',
            'status' => 'required|in:active,suspended,inactive',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $account = BusinessCreditAccount::with('user')->findOrFail($id);

        $oldLimit = (float) $account->credit_limit;
        $newLimit = (float) $validated['credit_limit'];
        $diff = $newLimit - $oldLimit;

        DB::transaction(function () use ($account, $validated, $newLimit, $diff) {
            $newAvailable = max(0, (float) $account->available_credit + $diff);

            $account->update([
                'credit_limit' => $newLimit,
                'available_credit' => $newAvailable,
                'repayment_due_days' => $validated['repayment_due_days'],
                'status' => $validated['status'],
                'admin_notes' => $validated['admin_notes'] ?? $account->admin_notes,
                'approved_at' => now(),
            ]);

            BusinessCreditTransaction::create([
                'business_credit_account_id' => $account->id,
                'type' => 'credit_assigned',
                'amount' => $newLimit,
                'balance_after' => $newAvailable,
                'reference_type' => 'admin_assignment',
                'notes' => 'Credit limit set to ₹' . number_format($newLimit, 2) . ' with ' . $validated['repayment_due_days'] . ' days repayment term.',
            ]);
        });

        // Notify Buyer
        try {
            UserNotification::create([
                'user_id' => $account->user_id,
                'title' => 'Business Credit Limit Approved! 💳',
                'message' => "Your JSS Business Pay-Later credit limit of ₹" . number_format($newLimit, 2) . " (Net {$validated['repayment_due_days']}) is now active.",
                'type' => 'credit_approved',
                'data' => [
                    'credit_account_id' => $account->id,
                    'credit_limit' => $newLimit,
                ],
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Credit limit updated successfully.',
            'data' => $account->fresh('user'),
        ], 200);
    }

    /**
     * Admin / Gateway: Record repayment to restore available credit (Feature 94).
     */
    public function recordRepayment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'reference_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $account = BusinessCreditAccount::findOrFail($id);
        $repaymentAmount = (float) $validated['amount'];

        DB::transaction(function () use ($account, $repaymentAmount, $validated) {
            $newUsed = max(0, (float) $account->used_credit - $repaymentAmount);
            $newAvailable = min((float) $account->credit_limit, (float) $account->available_credit + $repaymentAmount);

            $account->update([
                'used_credit' => $newUsed,
                'available_credit' => $newAvailable,
            ]);

            BusinessCreditTransaction::create([
                'business_credit_account_id' => $account->id,
                'type' => 'repayment',
                'amount' => $repaymentAmount,
                'balance_after' => $newAvailable,
                'reference_type' => 'repayment_receipt',
                'reference_id' => $validated['reference_id'] ?? null,
                'notes' => $validated['notes'] ?? 'Repayment recorded successfully.',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Repayment recorded and credit balance restored.',
            'data' => $account->fresh('transactions'),
        ], 200);
    }
}

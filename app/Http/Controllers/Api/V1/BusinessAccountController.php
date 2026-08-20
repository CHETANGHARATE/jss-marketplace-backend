<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BusinessAccount;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessAccountController extends Controller
{
    /**
     * Get authenticated user's business account / verification status (Feature 92 & 93).
     */
    public function getAccount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $account = BusinessAccount::where('user_id', $userId)->first();

        return response()->json([
            'success' => true,
            'data' => $account,
            'is_business_buyer' => $account && $account->status === 'verified',
        ], 200);
    }

    /**
     * Submit or update business account profile for verification (Feature 92 & 93).
     */
    public function storeOrUpdate(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'legal_business_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'business_type' => 'required|string|in:Sole Proprietorship,Partnership,LLP,Private Limited,Public Limited,MSME,Trust,Society,Other',
            'gstin' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i'],
            'pan' => ['nullable', 'string', 'max:15', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i'],
            'registered_address' => 'required|string|max:500',
            'billing_address' => 'nullable|string|max:500',
            'shipping_address' => 'nullable|string|max:500',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'contact_person' => 'required|string|max:150',
            'business_email' => 'required|email|max:150',
            'business_phone' => 'required|string|max:20',
            'website' => 'nullable|string|max:255',
            'annual_turnover' => 'nullable|string|max:100',
            'documents' => 'nullable|array',
        ]);

        $account = BusinessAccount::updateOrCreate(
            ['user_id' => $userId],
            array_merge($validated, [
                'status' => 'under_review',
                'rejection_reason' => null,
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Business profile submitted successfully. Our verification team will review your documents within 24-48 hours.',
            'data' => $account,
        ], 200);
    }

    /**
     * Admin: List all business buyer applications with status filter (Feature 93).
     */
    public function adminList(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $query = BusinessAccount::with('user')->latest();

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
     * Admin: Show specific business buyer application details.
     */
    public function adminShow(int $id): JsonResponse
    {
        $account = BusinessAccount::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $account,
        ], 200);
    }

    /**
     * Admin: Verify, approve, reject, or request changes for a business buyer application (Feature 93).
     */
    public function adminVerify(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:verified,rejected,changes_required',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $account = BusinessAccount::with('user')->findOrFail($id);

        $account->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'verified_at' => $validated['status'] === 'verified' ? now() : null,
        ]);

        // Send In-App notification to user
        try {
            $notifTitle = match ($validated['status']) {
                'verified' => 'Business Account Approved! 🎉',
                'rejected' => 'Business Account Application Update',
                'changes_required' => 'Changes Required on Business Application',
            };

            $notifMessage = match ($validated['status']) {
                'verified' => "Congratulations! Your business account for '{$account->legal_business_name}' has been verified. You can now access wholesale volume pricing and bulk RFQs.",
                'rejected' => "Your business application for '{$account->legal_business_name}' was not approved. Reason: " . ($validated['rejection_reason'] ?? 'Document verification incomplete.'),
                'changes_required' => "Please update your business application for '{$account->legal_business_name}'. Notes: " . ($validated['rejection_reason'] ?? 'Please provide updated GST certificate.'),
            };

            UserNotification::create([
                'user_id' => $account->user_id,
                'title' => $notifTitle,
                'message' => $notifMessage,
                'type' => 'business_verification',
                'data' => [
                    'business_account_id' => $account->id,
                    'status' => $account->status,
                ],
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => "Business application status updated to {$validated['status']}.",
            'data' => $account->fresh('user'),
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BuyerRequirement;
use App\Models\Product;
use App\Models\SampleRequest;
use App\Models\SellerBid;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class B2BMarketplaceController extends Controller
{
    /**
     * List open Buyer Requirements (Feature 86).
     */
    public function listRequirements(Request $request): JsonResponse
    {
        $categoryId = $request->query('category_id');

        $query = BuyerRequirement::with(['category', 'user.businessAccount', 'bids.seller.vendorStore'])
            ->withCount('bids')
            ->where('status', 'published')
            ->latest();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $requirements = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requirements->items(),
            'meta' => [
                'current_page' => $requirements->currentPage(),
                'last_page' => $requirements->lastPage(),
                'total' => $requirements->total(),
            ],
        ], 200);
    }

    /**
     * Buyer: Post a new Requirement (Feature 86).
     */
    public function postRequirement(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'category_id' => 'nullable|exists:categories,id',
            'quantity' => 'required|integer|min:1',
            'target_price' => 'nullable|numeric|min:0.01',
            'delivery_pincode' => 'nullable|string|max:10',
            'required_date' => 'nullable|date|after:today',
            'attachments' => 'nullable|array',
        ]);

        $reqNumber = 'REQ-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $requirement = BuyerRequirement::create([
            'requirement_number' => $reqNumber,
            'user_id' => $userId,
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'target_price' => $validated['target_price'] ?? null,
            'delivery_pincode' => $validated['delivery_pincode'] ?? null,
            'required_date' => $validated['required_date'] ?? null,
            'attachments' => $validated['attachments'] ?? [],
            'status' => 'published',
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Requirement posted successfully. Verified suppliers can now submit competitive bids.',
            'data' => $requirement->load('category'),
        ], 201);
    }

    /**
     * Seller: Bid on a Buyer Requirement (Feature 87).
     */
    public function bidOnRequirement(Request $request, int $requirementId): JsonResponse
    {
        $sellerId = $request->user()->id;
        $requirement = BuyerRequirement::findOrFail($requirementId);

        $validated = $request->validate([
            'bid_unit_price' => 'required|numeric|min:0.01',
            'moq' => 'nullable|integer|min:1',
            'lead_time_days' => 'required|integer|min:1',
            'shipping_cost' => 'nullable|numeric|min:0',
            'message' => 'nullable|string|max:1000',
        ]);

        $bid = SellerBid::updateOrCreate(
            [
                'buyer_requirement_id' => $requirement->id,
                'seller_id' => $sellerId,
            ],
            [
                'bid_unit_price' => $validated['bid_unit_price'],
                'moq' => $validated['moq'] ?? 1,
                'lead_time_days' => $validated['lead_time_days'],
                'shipping_cost' => (float) ($validated['shipping_cost'] ?? 0),
                'message' => $validated['message'] ?? null,
                'status' => 'submitted',
            ]
        );

        // Notify Buyer
        try {
            UserNotification::create([
                'user_id' => $requirement->user_id,
                'title' => 'New Seller Bid Received! 🏷️',
                'message' => "A vendor placed a bid of ₹{$validated['bid_unit_price']}/unit on your requirement '{$requirement->title}'.",
                'type' => 'bid_received',
                'data' => [
                    'requirement_id' => $requirement->id,
                    'bid_id' => $bid->id,
                ],
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Bid submitted successfully.',
            'data' => $bid->load('seller.vendorStore'),
        ], 201);
    }

    /**
     * Buyer: Submit Sample Order / Request (Features 84 & 85).
     */
    public function requestSample(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1|max:5',
            'shipping_address' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $sampleNumber = 'SMP-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $sample = SampleRequest::create([
            'sample_request_number' => $sampleNumber,
            'product_id' => $product->id,
            'buyer_id' => $userId,
            'seller_id' => $product->seller_id ?? 1,
            'quantity' => $validated['quantity'] ?? 1,
            'sample_price' => (float) ($product->offer_price ?? $product->price ?? 0),
            'shipping_address' => $validated['shipping_address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'requested',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sample request submitted to the seller for approval.',
            'data' => $sample->load(['product.primaryImage', 'seller']),
        ], 201);
    }

    /**
     * List Sample Requests for Buyer or Seller (Features 84 & 85).
     */
    public function listSampleRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SampleRequest::with(['product.primaryImage', 'buyer.businessAccount', 'seller.vendorStore'])
            ->latest();

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
        }

        $samples = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $samples->items(),
            'meta' => [
                'current_page' => $samples->currentPage(),
                'last_page' => $samples->lastPage(),
                'total' => $samples->total(),
            ],
        ], 200);
    }

    /**
     * Seller / Admin: Update Sample Request Status.
     */
    public function updateSampleStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,shipped,delivered,rejected',
            'courier_name' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $sample = SampleRequest::findOrFail($id);

        $sample->update([
            'status' => $validated['status'],
            'courier_name' => $validated['courier_name'] ?? $sample->courier_name,
            'tracking_number' => $validated['tracking_number'] ?? $sample->tracking_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sample request updated to {$validated['status']}.",
            'data' => $sample,
        ], 200);
    }
}

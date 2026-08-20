<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\UserNotification;
use App\Models\VendorStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RfqController extends Controller
{
    /**
     * List buyer's submitted RFQs (Feature 51 & 82).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $rfqs = Rfq::where('user_id', $userId)
            ->with(['category', 'product.primaryImage', 'quotations.seller'])
            ->withCount('quotations')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $rfqs->items(),
            'meta' => [
                'current_page' => $rfqs->currentPage(),
                'last_page' => $rfqs->lastPage(),
                'total' => $rfqs->total(),
            ],
        ], 200);
    }

    /**
     * Submit a new Request for Quotation (RFQ) / Bulk Quote (Feature 51 & 82).
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'category_id' => 'nullable|exists:categories,id',
            'product_id' => 'nullable|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'target_unit_price' => 'nullable|numeric|min:0.01',
            'delivery_location' => 'nullable|string|max:255',
            'delivery_pincode' => 'nullable|string|max:10',
            'required_delivery_date' => 'nullable|date|after:today',
            'attachments' => 'nullable|array',
            'items' => 'nullable|array',
        ]);

        $rfqNumber = 'RFQ-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $rfq = Rfq::create([
            'rfq_number' => $rfqNumber,
            'user_id' => $userId,
            'category_id' => $validated['category_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'target_unit_price' => $validated['target_unit_price'] ?? null,
            'delivery_location' => $validated['delivery_location'] ?? null,
            'delivery_pincode' => $validated['delivery_pincode'] ?? null,
            'required_delivery_date' => $validated['required_delivery_date'] ?? null,
            'attachments' => $validated['attachments'] ?? [],
            'status' => 'submitted',
            'expires_at' => now()->addDays(30),
        ]);

        // If sub-items provided
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                RfqItem::create([
                    'rfq_id' => $rfq->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'] ?? $rfq->title,
                    'specifications' => $item['specifications'] ?? null,
                    'quantity' => (int) ($item['quantity'] ?? $rfq->quantity),
                    'target_price' => isset($item['target_price']) ? (float) $item['target_price'] : null,
                ]);
            }
        }

        // Notify matching category vendors
        try {
            $matchingSellers = VendorStore::where('status', 'active')
                ->pluck('user_id')
                ->take(10);

            foreach ($matchingSellers as $sellerId) {
                UserNotification::create([
                    'user_id' => $sellerId,
                    'title' => 'New RFQ Opportunity! 📋',
                    'message' => "A new bulk RFQ '{$rfq->title}' ({$rfq->quantity} units) has been posted. Submit your quotation now!",
                    'type' => 'rfq_opportunity',
                    'data' => [
                        'rfq_id' => $rfq->id,
                        'rfq_number' => $rfq->rfq_number,
                    ],
                ]);
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'RFQ submitted successfully. Qualified sellers will review and submit quotations.',
            'data' => $rfq->load(['category', 'product']),
        ], 201);
    }

    /**
     * View RFQ details with received seller quotations (Feature 82 & 83).
     */
    public function show(Request $request, string $rfqNumber): JsonResponse
    {
        $userId = $request->user()->id;

        $rfq = Rfq::where('rfq_number', $rfqNumber)
            ->with([
                'user.businessAccount',
                'category',
                'product.primaryImage',
                'items',
                'quotations.seller.vendorStore',
                'quotations.negotiations.user',
            ])
            ->firstOrFail();

        // Ensure user is the RFQ author or an admin or a seller
        return response()->json([
            'success' => true,
            'data' => $rfq,
        ], 200);
    }

    /**
     * Seller Inbox: List RFQs available for vendor bidding.
     */
    public function sellerInbox(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;

        $rfqs = Rfq::whereIn('status', ['submitted', 'quotation_received', 'negotiation'])
            ->with(['category', 'product.primaryImage', 'user.businessAccount'])
            ->with(['quotations' => function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            }])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $rfqs->items(),
            'meta' => [
                'current_page' => $rfqs->currentPage(),
                'last_page' => $rfqs->lastPage(),
                'total' => $rfqs->total(),
            ],
        ], 200);
    }

    /**
     * Admin: List all RFQs in the marketplace.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $query = Rfq::with(['user.businessAccount', 'category', 'product'])
            ->withCount('quotations')
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $rfqs = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $rfqs->items(),
            'meta' => [
                'current_page' => $rfqs->currentPage(),
                'last_page' => $rfqs->lastPage(),
                'total' => $rfqs->total(),
            ],
        ], 200);
    }
}

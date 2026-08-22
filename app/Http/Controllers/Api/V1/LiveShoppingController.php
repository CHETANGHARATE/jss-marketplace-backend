<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LiveSession;
use App\Models\LiveSessionProduct;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LiveShoppingController extends Controller
{
    /**
     * Public list of live and scheduled shopping sessions (Feature 161).
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = LiveSession::with([
            'seller.vendorStore',
            'products.product.primaryImage',
        ]);

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['live', 'scheduled'])
                  ->orderByRaw("FIELD(status, 'live', 'scheduled', 'ended', 'cancelled')");
        }

        $sessions = $query->orderBy('scheduled_at', 'asc')->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ], 200);
    }

    /**
     * Show single live session with pinned products & stream metadata.
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $session = LiveSession::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->with([
                'seller.vendorStore',
                'products.product.primaryImage',
                'products.product.brand',
            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $session,
        ], 200);
    }

    /**
     * Increment viewer counter on joining live stream.
     */
    public function join(int $id): JsonResponse
    {
        $session = LiveSession::findOrFail($id);
        $session->increment('viewers_count');

        return response()->json([
            'success' => true,
            'viewers_count' => $session->viewers_count,
        ], 200);
    }

    /**
     * Send reaction / like in live stream.
     */
    public function like(int $id): JsonResponse
    {
        $session = LiveSession::findOrFail($id);
        $session->increment('likes_count');

        return response()->json([
            'success' => true,
            'likes_count' => $session->likes_count,
        ], 200);
    }

    /**
     * Seller / Admin create a new live shopping session.
     */
    public function store(Request $request): JsonResponse
    {
        $seller = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'thumbnail' => 'nullable|string|max:500',
            'stream_url' => 'nullable|string|max:500',
            'scheduled_at' => 'required|date|after:now',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'special_prices' => 'nullable|array',
        ]);

        $session = LiveSession::create([
            'seller_id' => $seller->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'stream_url' => $validated['stream_url'] ?? null,
            'status' => 'scheduled',
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        // Attach pinned products
        foreach ($validated['product_ids'] as $idx => $prodId) {
            LiveSessionProduct::create([
                'live_session_id' => $session->id,
                'product_id' => $prodId,
                'is_pinned' => $idx === 0, // First product pinned by default
                'special_live_price' => $validated['special_prices'][$prodId] ?? null,
                'sort_order' => $idx,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Live shopping session scheduled successfully.',
            'data' => $session->load('products.product'),
        ], 201);
    }

    /**
     * Update live session status (start stream, end stream).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $session = LiveSession::where('seller_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:scheduled,live,ended,cancelled',
            'stream_url' => 'nullable|string',
        ]);

        $updates = ['status' => $validated['status']];
        if (!empty($validated['stream_url'])) {
            $updates['stream_url'] = $validated['stream_url'];
        }

        if ($validated['status'] === 'live' && !$session->started_at) {
            $updates['started_at'] = now();
        }
        if ($validated['status'] === 'ended' && !$session->ended_at) {
            $updates['ended_at'] = now();
        }

        $session->update($updates);

        return response()->json([
            'success' => true,
            'message' => "Live session status updated to {$session->status}.",
            'data' => $session,
        ], 200);
    }
}

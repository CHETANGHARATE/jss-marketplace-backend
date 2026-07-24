<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportReviewRequest;
use App\Http\Requests\SubmitReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Display approved reviews and rating summary for a product.
     */
    public function index(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $reviews = Review::where('product_id', $product->id)
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->paginate(10);

        // Rating Summary Breakdown
        $ratingsBreakdown = [
            5 => Review::where('product_id', $product->id)->where('status', 'approved')->where('rating', 5)->count(),
            4 => Review::where('product_id', $product->id)->where('status', 'approved')->where('rating', 4)->count(),
            3 => Review::where('product_id', $product->id)->where('status', 'approved')->where('rating', 3)->count(),
            2 => Review::where('product_id', $product->id)->where('status', 'approved')->where('rating', 2)->count(),
            1 => Review::where('product_id', $product->id)->where('status', 'approved')->where('rating', 1)->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => [
                'average_rating' => (float) $product->rating,
                'total_reviews' => (int) $product->reviews_count,
                'breakdown' => $ratingsBreakdown,
            ],
            'data' => ReviewResource::collection($reviews),
        ], 200);
    }

    /**
     * Submit review for a verified purchase.
     */
    public function store(SubmitReviewRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $review = $this->reviewService->submitReview(
                $request->user(),
                $validated['product_id'],
                $validated['rating'],
                $validated['comment'],
                $validated['title'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully and is pending moderation.',
                'data' => new ReviewResource($review),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Report an inappropriate review.
     */
    public function report(ReportReviewRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $report = $this->reviewService->reportReview(
            $request->user(),
            $id,
            $validated['reason'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Review report submitted for admin review.',
        ], 201);
    }
}

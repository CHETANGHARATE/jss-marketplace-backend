<?php

namespace App\Services;

use App\Events\ReviewApprovedEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class ReviewService
{
    /**
     * Submit product review for a verified purchaser.
     */
    public function submitReview(User $user, int $productId, int $rating, string $comment, ?string $title = null): Review
    {
        $product = Product::findOrFail($productId);

        // Check if user has already reviewed this product
        $existingReview = Review::where('user_id', $user->id)->where('product_id', $product->id)->first();
        if ($existingReview) {
            throw new Exception("You have already submitted a review for this product.");
        }

        // Verify purchase via delivered orders containing product
        $verifiedOrder = Order::where('user_id', $user->id)
            ->whereHas('items', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->whereIn('status', ['delivered', 'confirmed', 'processing', 'shipped'])
            ->first();

        if (!$verifiedOrder) {
            throw new Exception("Only verified purchasers can submit product reviews.");
        }

        return Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_id' => $verifiedOrder->id,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'status' => 'pending',
            'is_verified_purchase' => true,
        ]);
    }

    /**
     * Moderate review status (Admin).
     */
    public function moderateReview(Review $review, string $status, ?string $reason = null): Review
    {
        return DB::transaction(function () use ($review, $status, $reason) {
            $review->update([
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? $reason : null,
            ]);

            if ($status === 'approved') {
                event(new ReviewApprovedEvent($review));
            }

            return $review->fresh();
        });
    }

    /**
     * Submit a customer product question.
     */
    public function askQuestion(User $user, int $productId, string $question): ProductQuestion
    {
        $product = Product::findOrFail($productId);

        return ProductQuestion::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'question' => $question,
            'is_public' => true,
        ]);
    }

    /**
     * Answer product question (Admin / Seller).
     */
    public function answerQuestion(int $questionId, User $answeredBy, string $answer): ProductQuestion
    {
        $question = ProductQuestion::findOrFail($questionId);

        $question->update([
            'answer' => $answer,
            'answered_by' => $answeredBy->id,
            'answered_at' => now(),
        ]);

        return $question->fresh(['answerer']);
    }

    /**
     * Report an inappropriate review.
     */
    public function reportReview(User $user, int $reviewId, string $reason, ?string $notes = null): ReviewReport
    {
        $review = Review::findOrFail($reviewId);

        return ReviewReport::create([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }
}

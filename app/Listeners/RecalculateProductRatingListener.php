<?php

namespace App\Listeners;

use App\Events\ReviewApprovedEvent;
use App\Models\Review;

class RecalculateProductRatingListener
{
    public function handle(ReviewApprovedEvent $event): void
    {
        $product = $event->review->product;

        if (!$product) {
            return;
        }

        $approvedReviews = Review::where('product_id', $product->id)->where('status', 'approved');

        $count = $approvedReviews->count();
        $avgRating = $count > 0 ? (float) $approvedReviews->avg('rating') : 0.0;

        $product->update([
            'rating' => round($avgRating, 2),
            'reviews_count' => $count,
        ]);
    }
}

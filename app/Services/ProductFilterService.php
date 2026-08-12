<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductFilterService
{
    /**
     * Apply filters to the Product Eloquent Builder based on Request parameters.
     */
    public function apply(Request $request, ?Builder $query = null): Builder
    {
        $query = $query ?? Product::query()->approved();

        // 1. Category Filter (by slug, ID, or category_id)
        if ($request->filled('category') || $request->filled('category_id')) {
            $cat = $request->input('category') ?? $request->input('category_id');
            $query->where(function ($q) use ($cat) {
                if (is_numeric($cat)) {
                    $catId = (int) $cat;
                    $q->where('category_id', $catId)
                      ->orWhere('subcategory_id', $catId)
                      ->orWhere('child_category_id', $catId)
                      ->orWhereHas('category', fn($cq) => $cq->where('id', $catId)->orWhere('parent_id', $catId));
                } else {
                    $q->whereHas('category', fn($cq) => $cq->where('slug', $cat))
                      ->orWhereHas('subcategory', fn($sq) => $sq->where('slug', $cat));
                }
            });
        }

        // 2. Subcategory Filter (supports subcategory, subcategory_id, subcategories, single or comma-separated)
        if ($request->filled('subcategory') || $request->filled('subcategory_id') || $request->filled('subcategories')) {
            $rawSubcat = $request->input('subcategory') ?? $request->input('subcategory_id') ?? $request->input('subcategories');
            $subcats = is_array($rawSubcat) ? $rawSubcat : explode(',', (string) $rawSubcat);
            $subcats = array_filter(array_map('trim', $subcats));

            if (!empty($subcats)) {
                $numericIds = array_filter($subcats, 'is_numeric');
                $slugs = array_diff($subcats, $numericIds);

                $query->where(function ($q) use ($numericIds, $slugs) {
                    if (!empty($numericIds)) {
                        $q->whereIn('subcategory_id', array_map('intval', $numericIds))
                          ->orWhereHas('subcategory', fn($sq) => $sq->whereIn('id', array_map('intval', $numericIds)));
                    }
                    if (!empty($slugs)) {
                        $q->orWhereHas('subcategory', fn($sq) => $sq->whereIn('slug', $slugs));
                    }
                });
            }
        }

        // 3. Brand Filter (supports array or comma-separated string)
        if ($request->filled('brand')) {
            $brands = is_array($request->input('brand')) 
                ? $request->input('brand') 
                : explode(',', $request->input('brand'));
            
            $query->whereHas('brand', function ($q) use ($brands) {
                $q->whereIn('slug', $brands)->orWhereIn('id', array_filter($brands, 'is_numeric'));
            });
        }

        // 4. Price Range Filter
        if ($request->filled('min_price')) {
            $query->where('offer_price', '>=', (float) $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('offer_price', '<=', (float) $request->input('max_price'));
        }

        // 5. Rating Threshold Filter
        if ($request->filled('rating')) {
            $query->where('rating', '>=', (float) $request->input('rating'));
        }

        // 6. Minimum Discount Filter
        if ($request->filled('discount')) {
            $query->where('discount_percent', '>=', (int) $request->input('discount'));
        }

        // 7. Stock Status Filter
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->input('stock_status'));
        }

        // 8. Keyword Search (Name, SKU, Description, Tags)
        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('tags', fn($t) => $t->whereRaw('LOWER(tag) LIKE ?', ["%{$search}%"]));
            });
        }

        // 9. In Stock First (optional prioritization)
        if ($request->boolean('in_stock_first')) {
            $query->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END');
        }

        // 10. Sorting
        $sortBy = $request->input('sort_by', 'newest');
        match ($sortBy) {
            'price_low_high' => $query->orderBy('offer_price', 'asc'),
            'price_high_low' => $query->orderBy('offer_price', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            'popularity' => $query->orderBy('reviews_count', 'desc'),
            'discount' => $query->orderBy('discount_percent', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query;
    }
}

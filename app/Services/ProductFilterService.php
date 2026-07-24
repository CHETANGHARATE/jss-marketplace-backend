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

        // 1. Category Filter (by slug or ID)
        if ($request->filled('category')) {
            $cat = $request->input('category');
            $query->whereHas('category', function ($q) use ($cat) {
                is_numeric($cat) ? $q->where('id', $cat) : $q->where('slug', $cat);
            });
        }

        // 2. Subcategory Filter
        if ($request->filled('subcategory')) {
            $subcat = $request->input('subcategory');
            $query->whereHas('subcategory', function ($q) use ($subcat) {
                is_numeric($subcat) ? $q->where('id', $subcat) : $q->where('slug', $subcat);
            });
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

        // 9. Sorting
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

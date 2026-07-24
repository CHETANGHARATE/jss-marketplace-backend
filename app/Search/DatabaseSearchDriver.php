<?php

namespace App\Search;

use App\Contracts\SearchDriverInterface;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DatabaseSearchDriver implements SearchDriverInterface
{
    public function search(array $params): array
    {
        $query = Product::where('status', 'published')->with(['category', 'brand']);

        // Search Query Text Filter
        if (!empty($params['q'])) {
            $searchTerm = '%' . trim($params['q']) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                    ->orWhere('description', 'LIKE', $searchTerm)
                    ->orWhere('sku', 'LIKE', $searchTerm);
            });
        }

        // Category Filter
        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        // Brand Filter
        if (!empty($params['brand_id'])) {
            $query->where('brand_id', $params['brand_id']);
        }

        // Price Range Filter
        if (!empty($params['min_price'])) {
            $query->where('original_price', '>=', (float) $params['min_price']);
        }
        if (!empty($params['max_price'])) {
            $query->where('original_price', '<=', (float) $params['max_price']);
        }

        // Rating Filter
        if (!empty($params['min_rating'])) {
            $query->where('rating', '>=', (float) $params['min_rating']);
        }

        // Sorting Engine
        $sortBy = $params['sort_by'] ?? 'latest';
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('original_price', 'ASC');
                break;
            case 'price_desc':
                $query->orderBy('original_price', 'DESC');
                break;
            case 'rating_desc':
                $query->orderBy('rating', 'DESC');
                break;
            case 'popularity':
                $query->orderBy('reviews_count', 'DESC');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $perPage = (int) ($params['per_page'] ?? 15);
        $paginator = $query->paginate($perPage);

        // Calculate Facets
        $facets = $this->calculateFacets($params);

        return [
            'paginator' => $paginator,
            'facets' => $facets,
        ];
    }

    public function autocomplete(string $queryStr): array
    {
        if (empty(trim($queryStr))) {
            return [];
        }

        $term = '%' . trim($queryStr) . '%';

        $products = Product::where('status', 'published')
            ->where('name', 'LIKE', $term)
            ->select('id', 'name', 'slug', 'original_price')
            ->take(5)
            ->get();

        $categories = Category::where('name->en', 'LIKE', $term)
            ->select('id', 'name', 'slug')
            ->take(3)
            ->get();

        return [
            'products' => $products,
            'categories' => $categories,
        ];
    }

    /**
     * Aggregate facets for category counts, brand counts, and min/max price range.
     */
    protected function calculateFacets(array $params): array
    {
        $baseQuery = Product::where('status', 'published');

        if (!empty($params['q'])) {
            $term = '%' . trim($params['q']) . '%';
            $baseQuery->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', $term)
                    ->orWhere('description', 'LIKE', $term);
            });
        }

        $minPrice = (float) ($baseQuery->min('original_price') ?? 0.00);
        $maxPrice = (float) ($baseQuery->max('original_price') ?? 0.00);

        // Category breakdown
        $categoryCounts = (clone $baseQuery)
            ->select('category_id', DB::raw('COUNT(id) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        // Brand breakdown
        $brandCounts = (clone $baseQuery)
            ->select('brand_id', DB::raw('COUNT(id) as total'))
            ->whereNotNull('brand_id')
            ->groupBy('brand_id')
            ->pluck('total', 'brand_id');

        return [
            'price_range' => [
                'min' => $minPrice,
                'max' => $maxPrice,
            ],
            'categories' => $categoryCounts,
            'brands' => $brandCounts,
        ];
    }
}

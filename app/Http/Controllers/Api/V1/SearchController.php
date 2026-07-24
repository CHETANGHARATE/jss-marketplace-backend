<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\SearchLog;
use App\Search\DatabaseSearchDriver;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected DatabaseSearchDriver $searchDriver;
    protected RecommendationService $recommendationService;

    public function __construct(DatabaseSearchDriver $searchDriver, RecommendationService $recommendationService)
    {
        $this->searchDriver = $searchDriver;
        $this->recommendationService = $recommendationService;
    }

    /**
     * Advanced Product Search with Dynamic Facets.
     */
    public function search(SearchProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->searchDriver->search($validated);

        // Async log search query for analytics
        if (!empty($validated['q'])) {
            SearchLog::create([
                'user_id' => $request->user()?->id,
                'query' => trim($validated['q']),
                'results_count' => $result['paginator']->total(),
                'filters' => $validated,
                'ip_address' => $request->ip(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'products' => ProductResource::collection($result['paginator']),
                'facets' => $result['facets'],
                'meta' => [
                    'current_page' => $result['paginator']->currentPage(),
                    'last_page' => $result['paginator']->lastPage(),
                    'total' => $result['paginator']->total(),
                ],
            ],
        ], 200);
    }

    /**
     * Autocomplete query suggestions.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $suggestions = $this->searchDriver->autocomplete($query);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ], 200);
    }

    /**
     * Related product recommendations for a given product.
     */
    public function related(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $related = $this->recommendationService->getRelatedProducts($product);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($related),
        ], 200);
    }

    /**
     * Trending product recommendations.
     */
    public function trending(): JsonResponse
    {
        $trending = $this->recommendationService->getTrendingProducts();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($trending),
        ], 200);
    }

    /**
     * Personalized recommendations for authenticated user.
     */
    public function personalized(Request $request): JsonResponse
    {
        $recommendations = $this->recommendationService->getPersonalizedRecommendations($request->user());

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($recommendations),
        ], 200);
    }
}

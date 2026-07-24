<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSearchSynonymRequest;
use App\Http\Resources\SearchLogResource;
use App\Http\Resources\SearchSynonymResource;
use App\Models\SearchLog;
use App\Models\SearchSynonym;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSearchController extends Controller
{
    /**
     * Search Analytics (popular queries & zero-result terms).
     */
    public function analytics(): JsonResponse
    {
        $popularQueries = SearchLog::select('query', DB::raw('COUNT(id) as count'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        $zeroResultQueries = SearchLog::select('query', DB::raw('COUNT(id) as count'))
            ->where('results_count', 0)
            ->groupBy('query')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        $recentLogs = SearchLog::with('user')->latest()->take(25)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'popular_queries' => $popularQueries,
                'zero_result_queries' => $zeroResultQueries,
                'recent_logs' => SearchLogResource::collection($recentLogs),
            ],
        ], 200);
    }

    /**
     * List search synonyms.
     */
    public function synonyms(): JsonResponse
    {
        $synonyms = SearchSynonym::latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => SearchSynonymResource::collection($synonyms),
        ], 200);
    }

    /**
     * Store search synonym.
     */
    public function storeSynonym(StoreSearchSynonymRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $synonym = SearchSynonym::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Search synonym created successfully.',
            'data' => new SearchSynonymResource($synonym),
        ], 201);
    }
}

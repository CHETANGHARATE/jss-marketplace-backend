<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Vision\VisualSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisualSearchController extends Controller
{
    protected VisualSearchService $visualService;

    public function __construct(VisualSearchService $visualService)
    {
        $this->visualService = $visualService;
    }

    /**
     * Search marketplace products via uploaded photo or camera snapshot (Features 59 + 61).
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required_without:image_data|nullable|file|image|max:10240', // 10MB max
            'image_data' => 'required_without:image|nullable|string', // Base64 Data URI
            'limit' => 'nullable|integer|min:1|max:30',
        ]);

        $input = $request->file('image') ?: $request->input('image_data');

        if (!$input) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide an image file or camera snapshot.',
            ], 422);
        }

        $limit = (int) $request->input('limit', 12);
        $results = $this->visualService->searchByImage($input, $limit);

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    }
}

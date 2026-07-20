<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttributeRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    /**
     * Display a listing of attributes.
     */
    public function index(Request $request): JsonResponse
    {
        $filterableOnly = $request->boolean('filterable');

        $query = Attribute::with(['values' => fn($q) => $q->orderBy('sort_order', 'asc')])
            ->orderBy('sort_order', 'asc');

        if ($filterableOnly) {
            $query->where('is_filterable', true);
        }

        return response()->json([
            'success' => true,
            'data' => AttributeResource::collection($query->get()),
        ], 200);
    }

    /**
     * Display a single attribute with values.
     */
    public function show(int $id): JsonResponse
    {
        $attribute = Attribute::with('values')->find($id);

        if (!$attribute) {
            return response()->json([
                'success' => false,
                'message' => 'Attribute not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AttributeResource($attribute),
        ], 200);
    }

    /**
     * Store a newly created attribute and its values (Admin only).
     */
    public function store(StoreAttributeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $values = $validated['values'] ?? [];
        unset($validated['values']);

        $attribute = Attribute::create($validated);

        foreach ($values as $index => $vData) {
            AttributeValue::create([
                'attribute_id' => $attribute->id,
                'value' => $vData['value'],
                'color_code' => $vData['color_code'] ?? null,
                'sort_order' => $index,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Attribute created successfully.',
            'data' => new AttributeResource($attribute->fresh('values')),
        ], 201);
    }

    /**
     * Delete an attribute (Admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            return response()->json([
                'success' => false,
                'message' => 'Attribute not found.',
            ], 404);
        }

        $attribute->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attribute deleted successfully.',
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeTemplate;
use App\Models\AttributeTemplateItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttributeTemplateController extends Controller
{
    /**
     * Display a listing of attribute templates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AttributeTemplate::with(['category', 'attributes.values']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $templates = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ], 200);
    }

    /**
     * Store a newly created attribute template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:attribute_templates,code',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'attributes' => 'nullable|array',
            'attributes.*.attribute_id' => 'required|exists:attributes,id',
            'attributes.*.is_required' => 'nullable|boolean',
            'attributes.*.sort_order' => 'nullable|integer',
        ]);

        $code = !empty($validated['code'])
            ? Str::slug($validated['code'])
            : Str::slug($validated['name']) . '-' . Str::random(4);

        $template = DB::transaction(function () use ($validated, $code) {
            $template = AttributeTemplate::create([
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'code' => $code,
                'description' => $validated['description'] ?? null,
            ]);

            if (!empty($validated['attributes'])) {
                foreach ($validated['attributes'] as $index => $attrData) {
                    AttributeTemplateItem::create([
                        'attribute_template_id' => $template->id,
                        'attribute_id' => $attrData['attribute_id'],
                        'is_required' => $attrData['is_required'] ?? false,
                        'sort_order' => $attrData['sort_order'] ?? $index,
                    ]);
                }
            }

            return $template;
        });

        return response()->json([
            'success' => true,
            'message' => 'Attribute template created successfully.',
            'data' => $template->load(['category', 'attributes.values']),
        ], 201);
    }

    /**
     * Display specified attribute template.
     */
    public function show(int $id): JsonResponse
    {
        $template = AttributeTemplate::with(['category', 'attributes.values', 'items'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $template,
        ], 200);
    }

    /**
     * Update specified attribute template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = AttributeTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:100|unique:attribute_templates,code,' . $template->id,
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'attributes' => 'nullable|array',
            'attributes.*.attribute_id' => 'required|exists:attributes,id',
            'attributes.*.is_required' => 'nullable|boolean',
            'attributes.*.sort_order' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($template, $validated) {
            if (isset($validated['name'])) $template->name = $validated['name'];
            if (isset($validated['code'])) $template->code = Str::slug($validated['code']);
            if (array_key_exists('category_id', $validated)) $template->category_id = $validated['category_id'];
            if (array_key_exists('description', $validated)) $template->description = $validated['description'];
            $template->save();

            if (isset($validated['attributes'])) {
                AttributeTemplateItem::where('attribute_template_id', $template->id)->delete();
                foreach ($validated['attributes'] as $index => $attrData) {
                    AttributeTemplateItem::create([
                        'attribute_template_id' => $template->id,
                        'attribute_id' => $attrData['attribute_id'],
                        'is_required' => $attrData['is_required'] ?? false,
                        'sort_order' => $attrData['sort_order'] ?? $index,
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Attribute template updated successfully.',
            'data' => $template->fresh(['category', 'attributes.values']),
        ], 200);
    }

    /**
     * Remove specified attribute template.
     */
    public function destroy(int $id): JsonResponse
    {
        $template = AttributeTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attribute template deleted successfully.',
        ], 200);
    }
}

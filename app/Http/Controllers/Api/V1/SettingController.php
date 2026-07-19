<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get all public application settings.
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group');

        $query = Setting::query();
        if ($group) {
            $query->where('group', $group);
        }

        $settings = $query->get();

        return response()->json([
            'success' => true,
            'data' => SettingResource::collection($settings),
        ], 200);
    }

    /**
     * Create or update a system setting (Admin only).
     */
    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $setting = Setting::set(
            $validated['key'],
            $validated['value'],
            $validated['group'] ?? 'general'
        );

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully.',
            'data' => new SettingResource($setting),
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * Get public application settings (or all settings if requested by authenticated Admin).
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group');
        $showAll = $request->query('all') && $request->user()?->isAdmin();

        // Admin can request all settings (including private credentials)
        if ($showAll) {
            $query = Setting::query();
            if ($group) {
                $query->where('group', $group);
            }
            return response()->json([
                'success' => true,
                'data' => SettingResource::collection($query->get()),
            ], 200);
        }

        // Public users receive ONLY public settings with caching
        $cacheKey = 'public_settings_' . ($group ?? 'all');

        $settings = Cache::remember($cacheKey, 3600, function () use ($group) {
            $query = Setting::where('is_public', true);
            if ($group) {
                $query->where('group', $group);
            }
            return $query->get();
        });

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
            $validated['group'] ?? 'general',
            $validated['is_public'] ?? true
        );

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully.',
            'data' => new SettingResource($setting),
        ], 200);
    }
}

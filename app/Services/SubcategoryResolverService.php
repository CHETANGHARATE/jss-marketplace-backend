<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubcategoryResolverService
{
    /**
     * Resolve an existing subcategory under a parent category or create a new one safely.
     * Prevents duplicate subcategory creation under the same parent_id.
     * Allows subcategories with the same name under DIFFERENT parent categories.
     *
     * @param int $parentId
     * @param string $subcategoryName
     * @param string|null $description
     * @param bool $createIfNotFound
     * @return Category|null
     */
    public function resolveOrCreateSubcategory(
        int $parentId,
        string $subcategoryName,
        ?string $description = null,
        bool $createIfNotFound = true
    ): ?Category {
        $trimmedName = trim($subcategoryName);
        if (empty($trimmedName)) {
            return null;
        }

        // 1. Verify parent category exists and is active
        $parentCat = Category::withTrashed()->find($parentId);
        if (!$parentCat) {
            Log::warning("SubcategoryResolverService: Parent category ID {$parentId} does not exist.");
            return null;
        }

        // 2. Normalize input string for search
        $slug = Str::slug($trimmedName);
        $normalizedSearch = strtolower(preg_replace('/[^a-z0-9]/i', '', str_ireplace(['and', '&'], '', $trimmedName)));
        $stemSearch = rtrim($normalizedSearch, 's');

        // Helper to match candidates
        $matchCandidate = function ($candidates) use ($slug, $trimmedName, $stemSearch) {
            foreach ($candidates as $cand) {
                $candSlug = $cand->slug;
                $candNameEn = '';
                if (is_array($cand->name)) {
                    $candNameEn = $cand->name['en'] ?? reset($cand->name);
                } else if (is_string($cand->name)) {
                    $decoded = json_decode($cand->name, true);
                    $candNameEn = is_array($decoded) ? ($decoded['en'] ?? reset($decoded)) : $cand->name;
                }

                // Direct slug, exact string, or Str::slug match
                $matched = (strcasecmp($candSlug, $slug) === 0 || 
                            strcasecmp($candNameEn, $trimmedName) === 0 || 
                            Str::slug($candNameEn) === $slug);

                // Normalized stem match (e.g. "Yellow Sapphires" vs "Yellow Sapphire")
                if (!$matched) {
                    $candSlugNorm = strtolower(preg_replace('/[^a-z0-9]/i', '', str_ireplace(['and', '&', '-'], '', $candSlug)));
                    $candNameNorm = strtolower(preg_replace('/[^a-z0-9]/i', '', str_ireplace(['and', '&'], '', $candNameEn)));

                    $stemCandSlug = rtrim($candSlugNorm, 's');
                    $stemCandName = rtrim($candNameNorm, 's');

                    if ($stemSearch === $stemCandSlug || 
                        $stemSearch === $stemCandName || 
                        (!empty($stemSearch) && !empty($stemCandName) && (str_starts_with($stemCandName, $stemSearch) || str_starts_with($stemSearch, $stemCandName)))) {
                        $matched = true;
                    }
                }

                if ($matched) {
                    if ($cand->trashed()) {
                        $cand->restore();
                        $cand->update(['deleted_at' => null, 'is_active' => true]);
                    }
                    if (!$cand->is_active) {
                        $cand->update(['is_active' => true]);
                    }
                    return $cand;
                }
            }
            return null;
        };

        // 3. Search existing subcategories under THIS specific parent_id
        $existingSubcat = $matchCandidate(
            Category::withTrashed()->where('parent_id', $parentId)->get()
        );

        if ($existingSubcat) {
            return $existingSubcat;
        }

        // 4. Check if an unattached/orphan category (parent_id IS NULL with zero products) exists that can be scoped under parent_id
        $orphanSubcat = $matchCandidate(
            Category::withTrashed()->whereNull('parent_id')->get()
        );

        if ($orphanSubcat) {
            $hasProducts = Product::where('category_id', $orphanSubcat->id)
                ->orWhere('subcategory_id', $orphanSubcat->id)
                ->exists();

            if (!$hasProducts) {
                $orphanSubcat->update([
                    'parent_id' => $parentId,
                    'is_active' => true,
                    'deleted_at' => null
                ]);
                Cache::flush();
                return $orphanSubcat;
            }
        }

        // 5. If dry-run (validation), return null without creating
        if (!$createIfNotFound) {
            return null;
        }

        // 6. Ensure slug is globally unique before creation
        $finalSlug = $slug;
        if (Category::withTrashed()->where('slug', $finalSlug)->exists()) {
            $finalSlug = $slug . '-' . $parentId;
            if (Category::withTrashed()->where('slug', $finalSlug)->exists()) {
                $finalSlug = $slug . '-' . $parentId . '-' . strtolower(Str::random(4));
            }
        }

        // 7. Create new subcategory
        $parentName = is_array($parentCat->name) ? ($parentCat->name['en'] ?? reset($parentCat->name)) : $parentCat->name;
        $descPayload = !empty($description) ? ['en' => trim($description)] : ['en' => "Collection of {$trimmedName} under {$parentName}."];

        $newSubcategory = Category::create([
            'parent_id' => $parentId,
            'name' => ['en' => $trimmedName],
            'slug' => $finalSlug,
            'description' => $descPayload,
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        Cache::flush();
        Log::info("SubcategoryResolverService: Successfully created new subcategory '{$trimmedName}' (ID: {$newSubcategory->id}) under parent ID {$parentId}.");

        return $newSubcategory;
    }
}

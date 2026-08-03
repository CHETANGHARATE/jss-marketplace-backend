<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Category;

return new class extends Migration
{
    /**
     * Run the migrations to merge and clean up duplicate categories in the database.
     */
    public function up(): void
    {
        // 1. Group categories by slug or English name
        $categories = DB::table('categories')->get();

        $seenSlugs = [];
        $duplicatesToMerge = [];

        foreach ($categories as $cat) {
            $slug = $cat->slug;
            
            // Try extracting English name if available
            $nameEn = '';
            if (!empty($cat->name)) {
                $decoded = json_decode($cat->name, true);
                if (is_array($decoded) && isset($decoded['en'])) {
                    $nameEn = strtolower(trim($decoded['en']));
                } elseif (is_string($cat->name)) {
                    $nameEn = strtolower(trim($cat->name));
                }
            }

            $key = $slug ?: $nameEn;
            if (!$key) continue;

            if (isset($seenSlugs[$key])) {
                $canonicalId = $seenSlugs[$key];
                $duplicateId = $cat->id;

                // Re-link parent_id in subcategories
                DB::table('categories')->where('parent_id', $duplicateId)->update(['parent_id' => $canonicalId]);

                // Re-link products
                DB::table('products')->where('category_id', $duplicateId)->update(['category_id' => $canonicalId]);
                DB::table('products')->where('subcategory_id', $duplicateId)->update(['subcategory_id' => $canonicalId]);

                // Re-link pivot tables if present
                if (Schema::hasTable('category_brands')) {
                    DB::table('category_brands')->where('category_id', $duplicateId)->update(['category_id' => $canonicalId]);
                }
                if (Schema::hasTable('category_attributes')) {
                    DB::table('category_attributes')->where('category_id', $duplicateId)->update(['category_id' => $canonicalId]);
                }

                $duplicatesToMerge[] = $duplicateId;
            } else {
                $seenSlugs[$key] = $cat->id;
            }
        }

        // 2. Permanently remove duplicate category rows
        if (!empty($duplicatesToMerge)) {
            DB::table('categories')->whereIn('id', $duplicatesToMerge)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for data cleanup migration
    }
};

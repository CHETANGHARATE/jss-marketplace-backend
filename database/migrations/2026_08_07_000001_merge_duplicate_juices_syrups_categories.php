<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\Product;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely merges duplicate "Juices & Syrups" categories (e.g. Category ID 43 into Category ID 26 or ID 1).
     */
    public function up(): void
    {
        // 1. Find all root categories matching "Juices & Syrup" or "Juices & Syrups"
        $rootCategories = Category::whereNull('parent_id')
            ->where(function ($q) {
                $q->where('slug', 'like', '%juices%syrup%')
                  ->orWhere('name', 'like', '%juices%syrup%')
                  ->orWhere('name->en', 'like', '%juices%syrup%');
            })
            ->orderBy('id', 'asc')
            ->get();

        if ($rootCategories->count() <= 1) {
            return;
        }

        // Primary Category is the lowest ID (e.g. ID 26 or ID 1)
        $primaryCategory = $rootCategories->first();
        $duplicateCategories = $rootCategories->slice(1);

        foreach ($duplicateCategories as $duplicate) {
            // Re-assign all products from duplicate category to primary category
            Product::where('category_id', $duplicate->id)
                ->update(['category_id' => $primaryCategory->id]);

            // Re-assign any subcategories from duplicate category to primary category
            Category::where('parent_id', $duplicate->id)
                ->update(['parent_id' => $primaryCategory->id]);

            // Delete duplicate category safely
            DB::table('categories')->where('id', $duplicate->id)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-time data cleanup migration; non-reversible safely.
    }
};

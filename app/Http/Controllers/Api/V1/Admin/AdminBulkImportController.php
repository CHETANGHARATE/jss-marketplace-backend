<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\ProductTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminBulkImportController extends Controller
{
    /**
     * Validate product records parsed from Excel / CSV before committing to database.
     * Always operates on the default database connection configured in .env.
     */
    public function validateImport(Request $request): JsonResponse
    {
        set_time_limit(300);
        ignore_user_abort(true);

        try {
            // Flexible products extraction
            $products = $request->input('products');
            if (empty($products)) {
                $rawContent = $request->getContent();
                if (!empty($rawContent)) {
                    $decoded = json_decode($rawContent, true);
                    $products = $decoded['products'] ?? (array_is_list($decoded) ? $decoded : null);
                }
            }

            if (empty($products) || !is_array($products)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The products field is required and must be a non-empty array.',
                ], 422);
            }

            $updateExisting = $request->boolean('update_existing', false);

            $connectionName = config('database.default');
            $databaseName = DB::connection()->getDatabaseName();

            Log::info("Bulk Import Validation Request Started", [
                'database_connection' => $connectionName,
                'database_name' => $databaseName,
                'total_rows' => count($products),
            ]);

            $summary = [
                'total' => count($products),
                'valid' => 0,
                'invalid' => 0,
                'duplicate_skus' => 0,
                'missing_images' => 0,
                'invalid_categories' => 0,
                'database_connection' => $connectionName,
                'database_name' => $databaseName,
            ];

            $validatedRows = [];

            foreach ($products as $index => $item) {
                $rowNumber = $index + 1;
                $rowErrors = [];
                $rowWarnings = [];

                // Extract standard field values
                $name = trim($item['Product Name'] ?? $item['name'] ?? '');
                $categoryName = trim($item['Category'] ?? $item['category'] ?? '');
                $subcategoryName = trim($item['Subcategory'] ?? $item['subcategory'] ?? '');
                $brandName = trim($item['Brand'] ?? $item['brand'] ?? '');
                $sku = trim($item['SKU'] ?? $item['sku'] ?? '');
                $slug = trim($item['Slug'] ?? $item['slug'] ?? '');
                $priceRaw = $item['Price'] ?? $item['price'] ?? null;
                $offerPriceRaw = $item['Offer Price'] ?? $item['offer_price'] ?? null;
                $stockRaw = $item['Stock'] ?? $item['stock'] ?? 100;
                $status = trim($item['Status'] ?? $item['status'] ?? 'Approved');
                $shortDescription = trim($item['Short Description'] ?? $item['short_description'] ?? '');
                $longDescription = trim($item['Long Description'] ?? $item['description'] ?? $item['long_description'] ?? '');
                $seoTitle = trim($item['SEO Title'] ?? $item['meta_title'] ?? $name);
                $seoDescription = trim($item['SEO Description'] ?? $item['meta_description'] ?? $shortDescription);
                $tags = trim($item['Tags'] ?? $item['tags'] ?? '');
                $imageFilename = trim($item['Image Filename'] ?? $item['image_filename'] ?? '');

                // 1. Required Fields Check
                if (empty($name)) {
                    $rowErrors[] = "Product Name is required.";
                }
                if (empty($categoryName)) {
                    $rowErrors[] = "Category is required.";
                }
                if (empty($sku)) {
                    $rowErrors[] = "SKU is required.";
                }

                // 2. Price Validation
                $price = is_numeric($priceRaw) ? (float)$priceRaw : 0;
                $offerPrice = is_numeric($offerPriceRaw) ? (float)$offerPriceRaw : $price;
                $stock = is_numeric($stockRaw) ? (int)$stockRaw : 100;

                if ($price <= 0) {
                    $rowErrors[] = "Price must be greater than 0.";
                }
                if ($offerPrice > $price) {
                    $rowErrors[] = "Offer Price (₹{$offerPrice}) cannot be greater than original Price (₹{$price}).";
                }

                // 3. SKU Uniqueness & Existing Check via Eloquent
                $existingProduct = Product::where('sku', $sku)->first();
                $isUpdate = false;

                if ($existingProduct) {
                    if ($updateExisting) {
                        $isUpdate = true;
                        $rowWarnings[] = "SKU '{$sku}' already exists. Will be updated.";
                    } else {
                        $rowErrors[] = "Duplicate SKU '{$sku}' already exists in marketplace.";
                        $summary['duplicate_skus']++;
                    }
                }

                // 4. Category & Subcategory Lookup using Environment-Independent Eloquent JSON syntax
                $catId = null;
                $subcatId = null;

                if (!empty($categoryName)) {
                    $categoryObj = Category::whereNull('parent_id')
                        ->where(function ($q) use ($categoryName) {
                            $q->where('slug', Str::slug($categoryName))
                              ->orWhere('name', 'like', "%{$categoryName}%")
                              ->orWhere('name->en', 'like', "%{$categoryName}%");
                        })->first();

                    if ($categoryObj) {
                        $catId = $categoryObj->id;
                        if (!empty($subcategoryName)) {
                            $subcatObj = Category::where('parent_id', $catId)
                                ->where(function ($q) use ($subcategoryName) {
                                    $q->where('slug', Str::slug($subcategoryName))
                                      ->orWhere('name', 'like', "%{$subcategoryName}%")
                                      ->orWhere('name->en', 'like', "%{$subcategoryName}%");
                                })->first();

                            if ($subcatObj) {
                                $subcatId = $subcatObj->id;
                            } else {
                                $rowWarnings[] = "Subcategory '{$subcategoryName}' not found. Will be created automatically.";
                            }
                        }
                    } else {
                        $rowWarnings[] = "Category '{$categoryName}' not found. Will be created automatically during import.";
                    }
                }

                // 5. Brand Lookup via Eloquent
                $brandId = null;
                if (!empty($brandName)) {
                    $brandObj = Brand::where('name', 'like', "%{$brandName}%")
                        ->orWhere('slug', Str::slug($brandName))
                        ->first();
                    if ($brandObj) {
                        $brandId = $brandObj->id;
                    } else {
                        $rowWarnings[] = "Brand '{$brandName}' not found. Will be created automatically during import.";
                    }
                }

                // 6. Image Filename Check
                $imageResolvedUrl = null;
                if (!empty($imageFilename)) {
                    $imageResolvedUrl = $this->resolveImageUrl($imageFilename);
                    if (!$imageResolvedUrl) {
                        $rowWarnings[] = "Image file '{$imageFilename}' not currently in public storage. Will check uploaded batch or local product folders.";
                    }
                } else {
                    $rowErrors[] = "Image Filename is missing.";
                    $summary['missing_images']++;
                }

                $isValid = (count($rowErrors) === 0);

                if ($isValid) {
                    $summary['valid']++;
                } else {
                    $summary['invalid']++;
                }

                $validatedRows[] = [
                    'row_number' => $rowNumber,
                    'name' => $name,
                    'category' => $categoryName,
                    'category_id' => $catId,
                    'subcategory' => $subcategoryName,
                    'subcategory_id' => $subcatId,
                    'brand' => $brandName,
                    'brand_id' => $brandId,
                    'sku' => $sku,
                    'slug' => !empty($slug) ? Str::slug($slug) : Str::slug($name) . '-' . strtolower(Str::random(5)),
                    'price' => $price,
                    'offer_price' => $offerPrice,
                    'stock' => $stock,
                    'status' => $status,
                    'short_description' => $shortDescription,
                    'description' => $longDescription,
                    'seo_title' => $seoTitle,
                    'seo_description' => $seoDescription,
                    'tags' => $tags,
                    'image_filename' => $imageFilename,
                    'image_url' => $imageResolvedUrl,
                    'is_valid' => $isValid,
                    'is_update' => $isUpdate,
                    'errors' => $rowErrors,
                    'warnings' => $rowWarnings,
                ];
            }

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'rows' => $validatedRows,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Bulk Import Validation Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate import file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute bulk import with per-row transaction isolation.
     * Always writes to Laravel's default configured database connection.
     */
    public function executeImport(Request $request): JsonResponse
    {
        set_time_limit(300);
        ignore_user_abort(true);

        try {
            // Multi-fallback products resolution
            $products = $request->input('products');

            if (empty($products)) {
                $rawContent = $request->getContent();
                if (!empty($rawContent)) {
                    $decoded = json_decode($rawContent, true);
                    if (is_array($decoded)) {
                        $products = $decoded['products'] ?? (array_is_list($decoded) ? $decoded : null);
                    }
                }
            }

            if (empty($products) || !is_array($products)) {
                Log::warning("Bulk Import Execute Empty Products Payload", [
                    'content_length' => $request->header('Content-Length'),
                    'content_type' => $request->header('Content-Type'),
                    'input_keys' => array_keys($request->all()),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'The products field is required and must be a non-empty array of product objects.',
                ], 422);
            }

            $updateExisting = $request->boolean('update_existing', false);
            $uploadedImagesMap = $request->input('images', []);

            $connectionName = config('database.default');
            $databaseName = DB::connection()->getDatabaseName();

            Log::info("==========================================");
            Log::info("Bulk Product Import Execution Started");
            Log::info("Database Connection: " . $connectionName);
            Log::info("Database Name: " . $databaseName);
            Log::info("Total Products to Process: " . count($products));
            Log::info("==========================================");

            $report = [
                'total_processed' => count($products),
                'imported_successfully' => 0,
                'skipped' => 0,
                'duplicate_sku' => 0,
                'missing_images' => 0,
                'invalid_category' => 0,
                'errors' => 0,
                'database_connection' => $connectionName,
                'database_name' => $databaseName,
                'failed_rows' => [],
            ];

            // Resolve seller ID
            $sellerId = Auth::check() ? Auth::id() : null;
            if (!$sellerId) {
                $adminUser = \App\Models\User::where('role', 'admin')->first()
                    ?? \App\Models\User::firstOrCreate(
                        ['email' => 'admin@jsssolutions.in'],
                        ['name' => 'Super Admin', 'password' => bcrypt('password123'), 'role' => 'admin', 'is_active' => true]
                    );
                $sellerId = $adminUser->id;
            }

            foreach ($products as $index => $item) {
                $rowNumber = $index + 1;

                // Execute each product inside an isolated row-level transaction on default connection
                DB::beginTransaction();

                try {
                    $name = trim($item['name'] ?? $item['Product Name'] ?? '');
                    $categoryName = trim($item['category'] ?? $item['Category'] ?? '');
                    $subcategoryName = trim($item['subcategory'] ?? $item['Subcategory'] ?? '');
                    $brandName = trim($item['brand'] ?? $item['Brand'] ?? '');
                    $sku = trim($item['sku'] ?? $item['SKU'] ?? '');
                    $slug = trim($item['slug'] ?? $item['Slug'] ?? '');
                    $price = (float)($item['price'] ?? $item['Price'] ?? 0);
                    $offerPrice = (float)($item['offer_price'] ?? $item['Offer Price'] ?? $price);
                    $stock = (int)($item['stock'] ?? $item['Stock'] ?? 100);
                    $statusInput = strtolower(trim($item['status'] ?? $item['Status'] ?? 'approved'));
                    $shortDescription = trim($item['short_description'] ?? $item['Short Description'] ?? '');
                    $description = trim($item['description'] ?? $item['Long Description'] ?? $shortDescription);
                    $seoTitle = trim($item['seo_title'] ?? $item['SEO Title'] ?? $name);
                    $seoDescription = trim($item['seo_description'] ?? $item['SEO Description'] ?? $shortDescription);
                    $tags = trim($item['tags'] ?? $item['Tags'] ?? '');
                    $imageFilename = trim($item['image_filename'] ?? $item['Image Filename'] ?? '');

                    // Validation checks
                    if (empty($name) || empty($sku) || $price <= 0) {
                        throw new \Exception("Mandatory product fields missing or invalid price.");
                    }

                    // SKU Duplicate check via Eloquent
                    $existingProduct = Product::where('sku', $sku)->first();
                    if ($existingProduct && !$updateExisting) {
                        $report['duplicate_sku']++;
                        throw new \Exception("Duplicate SKU '{$sku}' already exists in database.");
                    }

                    // 1. Resolve or Create Category using environment-independent Eloquent JSON query
                    $category = null;
                    if (!empty($categoryName)) {
                        $category = Category::whereNull('parent_id')
                            ->where(function ($q) use ($categoryName) {
                                $q->where('slug', Str::slug($categoryName))
                                  ->orWhere('name', 'like', "%{$categoryName}%")
                                  ->orWhere('name->en', 'like', "%{$categoryName}%");
                            })->first();

                        if (!$category) {
                            $category = Category::create([
                                'parent_id' => null,
                                'name' => ['en' => $categoryName],
                                'slug' => Str::slug($categoryName),
                                'is_active' => true,
                                'is_featured' => true,
                                'sort_order' => 0,
                            ]);
                        }
                    }

                    // 2. Resolve or Create Subcategory
                    $subcategory = null;
                    if ($category && !empty($subcategoryName)) {
                        $subcategory = Category::where('parent_id', $category->id)
                            ->where(function ($q) use ($subcategoryName) {
                                $q->where('slug', Str::slug($subcategoryName))
                                  ->orWhere('name', 'like', "%{$subcategoryName}%")
                                  ->orWhere('name->en', 'like', "%{$subcategoryName}%");
                            })->first();

                        if (!$subcategory) {
                            $subcategory = Category::create([
                                'parent_id' => $category->id,
                                'name' => ['en' => $subcategoryName],
                                'slug' => Str::slug($subcategoryName),
                                'is_active' => true,
                                'sort_order' => 0,
                            ]);
                        }
                    }

                    // 3. Resolve or Create Brand via Eloquent
                    $brand = null;
                    if (!empty($brandName)) {
                        $brand = Brand::where('name', 'like', "%{$brandName}%")
                            ->orWhere('slug', Str::slug($brandName))
                            ->first();

                        if (!$brand) {
                            $brand = Brand::create([
                                'name' => $brandName,
                                'slug' => Str::slug($brandName),
                                'is_active' => true,
                                'is_featured' => true,
                            ]);
                        }
                    }

                    // 4. Resolve Image File
                    $imageUrl = $uploadedImagesMap[$imageFilename] ?? $this->resolveAndCopyImage($imageFilename, $categoryName);
                    if (empty($imageUrl)) {
                        $imageUrl = "/storage/products/" . $imageFilename;
                    }

                    // Determine Status & Active State
                    $productStatus = in_array($statusInput, ['draft', 'pending', 'rejected', 'archived']) ? $statusInput : 'approved';
                    $isActive = ($productStatus === 'approved');

                    // Clean Slug
                    $finalSlug = !empty($slug) ? Str::slug($slug) : Str::slug($name);
                    if (Product::where('slug', $finalSlug)->where('sku', '!=', $sku)->exists()) {
                        $finalSlug = $finalSlug . '-' . strtolower(Str::random(5));
                    }

                    // Product Data Payload
                    $productData = [
                        'seller_id' => $sellerId,
                        'category_id' => $category ? $category->id : null,
                        'subcategory_id' => $subcategory ? $subcategory->id : null,
                        'brand_id' => $brand ? $brand->id : null,
                        'sku' => $sku,
                        'name' => ['en' => $name],
                        'slug' => $finalSlug,
                        'short_description' => ['en' => $shortDescription],
                        'description' => ['en' => $description],
                        'thumbnail' => $imageUrl,
                        'original_price' => $price,
                        'offer_price' => $offerPrice,
                        'stock_quantity' => $stock,
                        'stock_status' => $stock > 0 ? 'in_stock' : 'out_of_stock',
                        'status' => $productStatus,
                        'is_active' => $isActive,
                        'is_featured' => true,
                        'meta_title' => $seoTitle,
                        'meta_description' => $seoDescription,
                        'search_keywords' => $tags,
                        'highlights' => !empty($tags) ? array_map('trim', explode(',', $tags)) : null,
                    ];

                    if ($existingProduct && $updateExisting) {
                        $existingProduct->update($productData);
                        $product = $existingProduct;

                        // Clear & refresh images
                        $product->images()->delete();
                    } else {
                        $product = Product::create($productData);
                    }

                    // Insert Primary Image into ProductImages via Eloquent
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imageUrl,
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]);

                    // Insert Specifications if tags or details exist
                    if (!empty($tags)) {
                        $tagList = array_map('trim', explode(',', $tags));
                        foreach ($tagList as $idx => $tagVal) {
                            ProductTag::create([
                                'product_id' => $product->id,
                                'tag' => $tagVal,
                            ]);
                        }
                    }

                    DB::commit();
                    $report['imported_successfully']++;

                } catch (\Throwable $rowException) {
                    DB::rollBack();
                    $report['skipped']++;
                    $report['errors']++;

                    $report['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'product_name' => $name ?? 'Unknown',
                        'sku' => $sku ?? 'Unknown',
                        'error' => $rowException->getMessage(),
                    ];
                }
            }

            Log::info("Bulk Product Import Execution Completed", [
                'database_connection' => $connectionName,
                'database_name' => $databaseName,
                'imported_count' => $report['imported_successfully'],
                'failed_count' => count($report['failed_rows']),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bulk import completed. Successfully imported {$report['imported_successfully']} of {$report['total_processed']} products into {$databaseName}.",
                'report' => $report,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Bulk Import Execution Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute bulk import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve image URL from public disk or storage.
     */
    private function resolveImageUrl(string $filename): ?string
    {
        if (Storage::disk('public')->exists("products/{$filename}")) {
            return Storage::disk('public')->url("products/{$filename}");
        }

        if (Storage::disk('public')->exists("categories/{$filename}")) {
            return Storage::disk('public')->url("categories/{$filename}");
        }

        if (File::exists(public_path("storage/products/{$filename}"))) {
            return asset("storage/products/{$filename}");
        }

        return null;
    }

    /**
     * Locate local image file on PC or storage, copy to storage/app/public/products, and return public URL.
     */
    private function resolveAndCopyImage(string $filename, string $categoryName): ?string
    {
        // 1. Check existing storage
        $existing = $this->resolveImageUrl($filename);
        if ($existing) {
            return $existing;
        }

        // 2. Check local PC source folders
        $localPaths = [
            "D:\\Bright Touch Technologigs\\Website Client\\JayDeep\\Products pic\\Juices & Syrup\\{$filename}",
            "D:\\Bright Touch Technologigs\\Website Client\\JayDeep\\Products pic\\Pickle\\{$filename}",
            public_path("storage/products/{$filename}"),
            storage_path("app/public/products/{$filename}"),
        ];

        foreach ($localPaths as $path) {
            if (File::exists($path)) {
                $targetRelative = "products/{$filename}";
                Storage::disk('public')->put($targetRelative, File::get($path));
                return Storage::disk('public')->url($targetRelative);
            }
        }

        // Fallback relative path
        return "/storage/products/{$filename}";
    }
}

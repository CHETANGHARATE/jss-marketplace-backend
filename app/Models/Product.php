<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id',
        'category_id',
        'subcategory_id',
        'brand_id',
        'sku',
        'name',
        'slug',
        'short_description',
        'description',
        'thumbnail',
        'original_price',
        'offer_price',
        'discount_percent',
        'cost_price',
        'stock_status',
        'stock_quantity',
        'rating',
        'reviews_count',
        'is_featured',
        'is_trending',
        'is_active',
        'status',
        'rejection_reason',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'short_description' => 'array',
            'description' => 'array',
            'original_price' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_percent' => 'integer',
            'stock_quantity' => 'integer',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Calculate discount percentage automatically before saving.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Product $product) {
            if ($product->original_price > 0 && $product->offer_price < $product->original_price) {
                $discount = (($product->original_price - $product->offer_price) / $product->original_price) * 100;
                $product->discount_percent = (int) round($discount);
            } else {
                $product->discount_percent = 0;
            }
        });
    }

    /**
     * Category relationship.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Subcategory relationship.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * Brand relationship.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Seller / Vendor relationship.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Product gallery images relationship.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order', 'asc');
    }

    /**
     * Primary thumbnail image relationship.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_id')->where('is_primary', true);
    }

    /**
     * Product specifications key-value table.
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class, 'product_id')->orderBy('sort_order', 'asc');
    }

    /**
     * Search & Filter tags.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(ProductTag::class, 'product_id');
    }

    /**
     * Associated Attribute Values (Size, Color, Material, etc.).
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_values', 'product_id', 'attribute_value_id');
    }

    /**
     * Polymorphic Media attachments.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->orderBy('sort_order', 'asc');
    }

    /**
     * Scope for active & approved products.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_active', true)->where('status', 'approved');
    }
}

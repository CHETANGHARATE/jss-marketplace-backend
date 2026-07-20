<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'website',
        'description',
        'is_featured',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Categories associated with this brand.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_brands', 'brand_id', 'category_id');
    }

    /**
     * Media associated with this brand.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->orderBy('sort_order', 'asc');
    }
}

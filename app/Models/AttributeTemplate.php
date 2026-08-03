<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'description',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AttributeTemplateItem::class, 'attribute_template_id')->orderBy('sort_order', 'asc');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'attribute_template_items', 'attribute_template_id', 'attribute_id')
            ->withPivot('is_required', 'sort_order')
            ->orderBy('attribute_template_items.sort_order', 'asc');
    }
}

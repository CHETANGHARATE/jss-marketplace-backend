<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = [
            [
                'name' => ['en' => 'Size', 'hi' => 'आकार'],
                'code' => 'size',
                'type' => 'select',
                'is_filterable' => true,
                'values' => [
                    ['value' => 'S', 'sort_order' => 1],
                    ['value' => 'M', 'sort_order' => 2],
                    ['value' => 'L', 'sort_order' => 3],
                    ['value' => 'XL', 'sort_order' => 4],
                    ['value' => 'XXL', 'sort_order' => 5],
                    ['value' => 'Size 7', 'sort_order' => 6],
                    ['value' => 'Size 8', 'sort_order' => 7],
                    ['value' => 'Size 9', 'sort_order' => 8],
                ]
            ],
            [
                'name' => ['en' => 'Color', 'hi' => 'रंग'],
                'code' => 'color',
                'type' => 'color_picker',
                'is_filterable' => true,
                'values' => [
                    ['value' => 'Red', 'color_code' => '#FF0000', 'sort_order' => 1],
                    ['value' => 'Blue', 'color_code' => '#0000FF', 'sort_order' => 2],
                    ['value' => 'Black', 'color_code' => '#000000', 'sort_order' => 3],
                    ['value' => 'White', 'color_code' => '#FFFFFF', 'sort_order' => 4],
                    ['value' => 'Green', 'color_code' => '#008000', 'sort_order' => 5],
                ]
            ],
            [
                'name' => ['en' => 'Storage Capacity', 'hi' => 'स्टोरेज'],
                'code' => 'storage',
                'type' => 'select',
                'is_filterable' => true,
                'values' => [
                    ['value' => '64GB', 'sort_order' => 1],
                    ['value' => '128GB', 'sort_order' => 2],
                    ['value' => '256GB', 'sort_order' => 3],
                    ['value' => '512GB', 'sort_order' => 4],
                    ['value' => '1TB', 'sort_order' => 5],
                ]
            ],
            [
                'name' => ['en' => 'Material', 'hi' => 'सामग्री'],
                'code' => 'material',
                'type' => 'select',
                'is_filterable' => true,
                'values' => [
                    ['value' => 'Cotton', 'sort_order' => 1],
                    ['value' => 'Leather', 'sort_order' => 2],
                    ['value' => 'Polyester', 'sort_order' => 3],
                    ['value' => 'Wood', 'sort_order' => 4],
                ]
            ],
        ];

        foreach ($attributes as $index => $attrData) {
            $attribute = Attribute::updateOrCreate(
                ['code' => $attrData['code']],
                [
                    'name' => $attrData['name'],
                    'type' => $attrData['type'],
                    'is_filterable' => $attrData['is_filterable'],
                    'sort_order' => $index,
                ]
            );

            foreach ($attrData['values'] as $vData) {
                AttributeValue::updateOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'value' => $vData['value'],
                    ],
                    [
                        'color_code' => $vData['color_code'] ?? null,
                        'sort_order' => $vData['sort_order'],
                    ]
                );
            }
        }
    }
}

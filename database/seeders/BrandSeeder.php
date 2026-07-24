<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'Samsung', 'slug' => 'samsung', 'is_featured' => true],
            ['name' => 'Apple', 'slug' => 'apple', 'is_featured' => true],
            ['name' => 'Nike', 'slug' => 'nike', 'is_featured' => true],
            ['name' => 'Adidas', 'slug' => 'adidas', 'is_featured' => true],
            ['name' => 'Sony', 'slug' => 'sony', 'is_featured' => true],
            ['name' => 'Philips', 'slug' => 'philips', 'is_featured' => false],
            ['name' => 'Asus', 'slug' => 'asus', 'is_featured' => true],
            ['name' => 'LG', 'slug' => 'lg', 'is_featured' => false],
            ['name' => "Levi's", 'slug' => 'levis', 'is_featured' => true],
        ];

        foreach ($brands as $index => $brand) {
            Brand::updateOrCreate(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'is_featured' => $brand['is_featured'],
                    'is_active' => true,
                    'sort_order' => $index,
                    'description' => $brand['name'] . ' Official Brand Store',
                    'meta_title' => $brand['name'] . ' Products | JSS Solutions',
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoriesData = [
            [
                'name' => ['en' => 'Fashion', 'hi' => 'फैशन', 'mr' => 'फॅशन'],
                'slug' => 'fashion',
                'description' => ['en' => 'Clothing, footwear, and apparel', 'hi' => 'कपड़े और जूते'],
                'icon' => 'Shirt',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => "Men's Wear", 'hi' => 'पुरुषों के कपड़े'], 'slug' => 'mens-wear'],
                    ['name' => ['en' => "Women's Wear", 'hi' => 'महिलाओं के कपड़े'], 'slug' => 'womens-wear'],
                    ['name' => ['en' => 'Kids & Baby', 'hi' => 'बच्चों के कपड़े'], 'slug' => 'kids-baby'],
                    ['name' => ['en' => 'Footwear', 'hi' => 'जूते'], 'slug' => 'footwear'],
                    ['name' => ['en' => 'Watches & Accessories', 'hi' => 'घड़ियां'], 'slug' => 'watches-accessories'],
                ]
            ],
            [
                'name' => ['en' => 'Electronics', 'hi' => 'इलेक्ट्रॉनिक्स', 'mr' => 'इलेक्ट्रॉनिक्स'],
                'slug' => 'electronics',
                'description' => ['en' => 'Smartphones, laptops, and audio gear', 'hi' => 'स्मार्टफोन और लैपटॉप'],
                'icon' => 'Laptop',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Smartphones', 'hi' => 'स्मार्टफोन'], 'slug' => 'smartphones'],
                    ['name' => ['en' => 'Laptops & Computers', 'hi' => 'लैपटॉप'], 'slug' => 'laptops-computers'],
                    ['name' => ['en' => 'Audio & Headphones', 'hi' => 'हेडफोन'], 'slug' => 'audio-headphones'],
                    ['name' => ['en' => 'Cameras & Drones', 'hi' => 'कैमरे'], 'slug' => 'cameras-drones'],
                    ['name' => ['en' => 'Wearable Gadgets', 'hi' => 'स्मार्टवॉच'], 'slug' => 'wearable-gadgets'],
                ]
            ],
            [
                'name' => ['en' => 'Home & Kitchen', 'hi' => 'होम और किचन', 'mr' => 'होम आणि किचन'],
                'slug' => 'home-kitchen',
                'description' => ['en' => 'Furniture, decor, and cookware', 'hi' => 'फर्नीचर और रसोई का सामान'],
                'icon' => 'Sofa',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Furniture', 'hi' => 'फर्नीचर'], 'slug' => 'furniture'],
                    ['name' => ['en' => 'Kitchen Appliances', 'hi' => 'रसोई के उपकरण'], 'slug' => 'kitchen-appliances'],
                    ['name' => ['en' => 'Home Decor', 'hi' => 'घर की सजावट'], 'slug' => 'home-decor'],
                    ['name' => ['en' => 'Lighting & Lamps', 'hi' => 'लाइट्स'], 'slug' => 'lighting-lamps'],
                ]
            ],
            [
                'name' => ['en' => 'Agriculture & Seeds', 'hi' => 'कृषि और बीज', 'mr' => 'शेती आणि बियाणे'],
                'slug' => 'agriculture-seeds',
                'description' => ['en' => 'High yield seeds, fertilizers, and farm equipment', 'hi' => 'जैविक बीज और उर्वरक'],
                'icon' => 'Sprout',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Organic Seeds', 'hi' => 'जैविक बीज'], 'slug' => 'organic-seeds'],
                    ['name' => ['en' => 'Fertilizers & Soil Boosters', 'hi' => 'उर्वरक'], 'slug' => 'fertilizers'],
                    ['name' => ['en' => 'Irrigation & Tools', 'hi' => 'सिंचाई उपकरण'], 'slug' => 'irrigation-tools'],
                ]
            ],
            [
                'name' => ['en' => 'Beauty & Care', 'hi' => 'सौंदर्य और देखभाल', 'mr' => 'सौंदर्य आणि काळजी'],
                'slug' => 'beauty-care',
                'description' => ['en' => 'Skincare, haircare, and personal grooming', 'hi' => 'त्वचा और बालों की देखभाल'],
                'icon' => 'Sparkles',
                'is_featured' => false,
                'subcategories' => [
                    ['name' => ['en' => 'Skincare', 'hi' => 'स्किनकेयर'], 'slug' => 'skincare'],
                    ['name' => ['en' => 'Haircare', 'hi' => 'हेयरकेयर'], 'slug' => 'haircare'],
                    ['name' => ['en' => 'Fragrances', 'hi' => 'इत्र'], 'slug' => 'fragrances'],
                ]
            ],
        ];

        foreach ($categoriesData as $catIndex => $cData) {
            $parent = Category::updateOrCreate(
                ['slug' => $cData['slug']],
                [
                    'name' => $cData['name'],
                    'description' => $cData['description'],
                    'icon' => $cData['icon'],
                    'is_featured' => $cData['is_featured'],
                    'is_active' => true,
                    'sort_order' => $catIndex,
                    'meta_title' => $cData['name']['en'] . ' | JSS Marketplace',
                    'meta_description' => $cData['description']['en'],
                ]
            );

            if (isset($cData['subcategories'])) {
                foreach ($cData['subcategories'] as $subIndex => $subData) {
                    Category::updateOrCreate(
                        ['slug' => $subData['slug']],
                        [
                            'parent_id' => $parent->id,
                            'name' => $subData['name'],
                            'description' => ['en' => 'Subcategory under ' . $parent->name['en']],
                            'is_featured' => false,
                            'is_active' => true,
                            'sort_order' => $subIndex,
                        ]
                    );
                }
            }
        }
    }
}

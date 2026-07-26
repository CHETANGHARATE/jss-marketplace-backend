<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds for exactly 20 marketplace categories in exact order.
     */
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'juices_syrups',
                'name' => ['en' => 'Juices & Syrups', 'hi' => 'रस और शर्बत', 'mr' => 'रस आणि सरबत'],
                'description' => ['en' => 'Pure fruit juices, herbal syrups, and refreshing concentrates'],
                'icon' => 'GlassWater',
                'is_featured' => true,
            ],
            [
                'slug' => 'religious_pooja_items',
                'name' => ['en' => 'Religious & Pooja Items', 'hi' => 'धार्मिक और पूजा सामग्री', 'mr' => 'धार्मिक आणि पूजा साहित्य'],
                'description' => ['en' => 'Brass diyas, camphor, incense sticks, samagri, and thalis'],
                'icon' => 'Flower2',
                'is_featured' => true,
            ],
            [
                'slug' => 'cosmetics',
                'name' => ['en' => 'Cosmetics', 'hi' => 'कॉस्मेटिक्स', 'mr' => 'कॉस्मेटिक्स'],
                'description' => ['en' => 'Ayurvedic creams, organic makeup, lipstick, and beauty care'],
                'icon' => 'Paintbrush',
                'is_featured' => true,
            ],
            [
                'slug' => 'beauty_personal_care',
                'name' => ['en' => 'Beauty & Personal Care', 'hi' => 'सौंदर्य और व्यक्तिगत देखभाल', 'mr' => 'सौंदर्य आणि वैयक्तिक काळजी'],
                'description' => ['en' => 'Skincare, hair oils, herbal soaps, and grooming kits'],
                'icon' => 'Sparkles',
                'is_featured' => true,
            ],
            [
                'slug' => 'footwear',
                'name' => ['en' => 'Footwear', 'hi' => 'जूते और चप्पल', 'mr' => 'पादत्राणे'],
                'description' => ['en' => 'Kolhapuri chappals, ethnic juttis, leather shoes, and sandals'],
                'icon' => 'Footprints',
                'is_featured' => true,
            ],
            [
                'slug' => 'pickles',
                'name' => ['en' => 'Pickles', 'hi' => 'अचार', 'mr' => 'लोणचे'],
                'description' => ['en' => 'Traditional mango, lemon, garlic, and mixed chili pickles'],
                'icon' => 'Utensils',
                'is_featured' => true,
            ],
            [
                'slug' => 'masale_spices',
                'name' => ['en' => 'Masale', 'hi' => 'मसाले', 'mr' => 'मसाले'],
                'description' => ['en' => 'Authentic Maharashtrian spices, turmeric, chili, and garam masala'],
                'icon' => 'Flame',
                'is_featured' => true,
            ],
            [
                'slug' => 'fashion',
                'name' => ['en' => 'Fashion', 'hi' => 'फैशन कपड़े', 'mr' => 'फॅशन कपडे'],
                'description' => ['en' => 'Men\'s kurtas, handloom sarees, ethnic wear, and western apparel'],
                'icon' => 'Shirt',
                'is_featured' => true,
            ],
            [
                'slug' => 'jewellery',
                'name' => ['en' => 'Jewellery', 'hi' => 'आभूषण', 'mr' => 'दागिने'],
                'description' => ['en' => 'Kundan sets, gold plated necklaces, temple jewellery, and bangles'],
                'icon' => 'Gem',
                'is_featured' => true,
            ],
            [
                'slug' => 'agriculture',
                'name' => ['en' => 'Agriculture', 'hi' => 'कृषि और बीज', 'mr' => 'शेती आणि बियाणे'],
                'description' => ['en' => 'Organic seeds, bio-fertilizers, neem oils, and farm tools'],
                'icon' => 'Sprout',
                'is_featured' => true,
            ],
            [
                'slug' => 'auto_accessories',
                'name' => ['en' => 'Auto Accessories', 'hi' => 'ऑटो एक्सेसरीज', 'mr' => 'ऑटो ॲक्सेसरीज'],
                'description' => ['en' => 'Car seat cushions, bike covers, polishes, and air pumps'],
                'icon' => 'Car',
                'is_featured' => false,
            ],
            [
                'slug' => 'local_homemade',
                'name' => ['en' => 'Local & Homemade Products', 'hi' => 'स्थानीय और घरेलू उत्पाद', 'mr' => 'स्थानिक व घरगुती उत्पादने'],
                'description' => ['en' => 'Pure desi cow ghee, handmade soaps, jaggery, and snacks'],
                'icon' => 'Home',
                'is_featured' => true,
            ],
            [
                'slug' => 'pooja_spiritual',
                'name' => ['en' => 'Pooja & Spiritual', 'hi' => 'पूजा और आध्यात्मिक', 'mr' => 'पूजा आणि अध्यात्मिक'],
                'description' => ['en' => 'Rudraksha beads, brass bells, sandalwood, and spiritual idols'],
                'icon' => 'Landmark',
                'is_featured' => false,
            ],
            [
                'slug' => 'gifts_handicrafts',
                'name' => ['en' => 'Gifts & Handicrafts', 'hi' => 'उपहार और हस्तशिल्प', 'mr' => 'भेटवस्तू आणि हस्तकला'],
                'description' => ['en' => 'Wooden marble inlay art, brass statues, and terracotta crafts'],
                'icon' => 'Gift',
                'is_featured' => true,
            ],
            [
                'slug' => 'baby_kids',
                'name' => ['en' => 'Baby & Kids', 'hi' => 'बच्चों के उत्पाद', 'mr' => 'लहान मुलांचे साहित्य'],
                'description' => ['en' => 'Organic cotton clothing, wooden toys, and baby care'],
                'icon' => 'Baby',
                'is_featured' => false,
            ],
            [
                'slug' => 'oil',
                'name' => ['en' => 'Oil', 'hi' => 'खाना पकाने का तेल', 'mr' => 'खाद्यतेल'],
                'description' => ['en' => 'Cold pressed mustard oil, virgin coconut oil, and groundnut oil'],
                'icon' => 'Droplet',
                'is_featured' => true,
            ],
            [
                'slug' => 'papad_kurdai',
                'name' => ['en' => 'Papad & Kurdai', 'hi' => 'पापड़ और कुरडई', 'mr' => 'पापड आणि कुरडई'],
                'description' => ['en' => 'Traditional urad papad, wheat kurdai, and rice flour chakli'],
                'icon' => 'Cookie',
                'is_featured' => true,
            ],
            [
                'slug' => 'astro_stone',
                'name' => ['en' => 'Astro Stone', 'hi' => 'ज्योतिष रत्न', 'mr' => 'ज्योतिष रत्न'],
                'description' => ['en' => 'Natural yellow sapphire, blue sapphire, ruby, and emeralds'],
                'icon' => 'Moon',
                'is_featured' => false,
            ],
            [
                'slug' => 'diwali_faral',
                'name' => ['en' => 'Diwali Faral', 'hi' => 'दिवाली फराल', 'mr' => 'दिवाळी फराळ'],
                'description' => ['en' => 'Besan ladoo, bhakarwadi, poha chivda, and shakarpara sweets'],
                'icon' => 'PartyPopper',
                'is_featured' => true,
            ],
            [
                'slug' => 'electronics',
                'name' => ['en' => 'Electronics', 'hi' => 'इलेक्ट्रॉनिक्स', 'mr' => 'इलेक्ट्रॉनिक्स'],
                'description' => ['en' => 'Smartwatches, wireless speakers, LED TVs, and power banks'],
                'icon' => 'Laptop',
                'is_featured' => true,
            ],
        ];

        foreach ($categories as $index => $cData) {
            Category::updateOrCreate(
                ['slug' => $cData['slug']],
                [
                    'parent_id' => null,
                    'name' => $cData['name'],
                    'description' => $cData['description'],
                    'icon' => $cData['icon'],
                    'image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=500&auto=format&fit=crop&q=80',
                    'is_featured' => $cData['is_featured'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'meta_title' => $cData['name']['en'] . ' | JSS Marketplace',
                    'meta_description' => $cData['description']['en'],
                ]
            );
        }
    }
}

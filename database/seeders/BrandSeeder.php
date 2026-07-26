<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds for 64 realistic brands.
     */
    public function run(): void
    {
        $brands = [
            // Juices & Syrups
            'Real Fruit', 'Dabur Nature', 'Hamdard Rooh', 'Mapro Foods',
            // Religious & Pooja Items
            'Panchamrut Divine', 'Pitambari Care', 'Om Shanti Samagri', 'Cycle Pure Agarbatti',
            // Cosmetics
            'Forest Essentials', 'Biotique Botanicals', 'Lotus Herbals', 'Lakme India',
            // Beauty & Personal Care
            'Himalaya Herbals', 'Patanjali Ayurved', 'Mamaearth Organic', 'Nivea Care',
            // Footwear
            'Kolhapuri Craft', 'Bata India', 'Nike Sports', 'Adidas Athletic',
            // Pickles
            'Mother\'s Recipe', 'Bedekar Pickles', 'Pravin Spices', 'Priya Foods',
            // Masale
            'Everest Masale', 'MDH Spices', 'Badshah Masala', 'Tata Sampann',
            // Fashion
            'FabIndia Ethnic', 'Manyavar', 'Raymond Fine', 'Levi\'s Denim',
            // Jewellery
            'Tanishq Gold', 'Malabar Gold', 'Kalyan Jewellers', 'Senco Gold',
            // Agriculture
            'Syngenta Seeds', 'Nuziveedu Seeds', 'UPL Agriculture', 'Bayer Crop',
            // Auto Accessories
            'Bosch Auto', 'Sparco Racing', 'AutoForm Covers', 'Steelbird Helmets',
            // Local & Homemade Products
            'Chitale Bandhu', 'Suhana Flavors', 'Desi Ghee Co', 'Sattvic Foods',
            // Pooja & Spiritual
            'Divine Crafts', 'Rudra Centre', 'Giri Trading', 'Astha Spiritual',
            // Gifts & Handicrafts
            'Channapatna Toys', 'Rajasthan Artisans', 'Terracotta India', 'Cottage Industries',
            // Baby & Kids
            'Johnson & Baby', 'Chicco Care', 'FirstCry Kids', 'Sebamed Baby',
            // Oil
            'Saffola Gold', 'Fortune Oil', 'Dhara Pure', 'Gemini Oil',
            // Papad & Kurdai
            'Lijjat Papad', 'Mother Special Papad', 'Satara Kurdai', 'Mahila Griha Udyog',
            // Astro Stone
            'Ratna Gems', 'GemPundit Certified', 'AstroVed Stones', 'Precious Gems House',
            // Diwali Faral
            'Chitale Faral', 'Laxmi Narayan Chivda', 'Kaka Halwai', 'MMB Sweets',
            // Electronics
            'Samsung Electronics', 'Apple Inc', 'Sony Audio', 'Boat Lifestyle',
        ];

        foreach ($brands as $index => $brandName) {
            $slug = Str::slug($brandName);
            Brand::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $brandName,
                    'logo' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=300&auto=format&fit=crop&q=80',
                    'description' => 'Official brand store for ' . $brandName,
                    'is_featured' => $index < 20,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'meta_title' => $brandName . ' | JSS Marketplace',
                    'meta_description' => 'Buy authentic ' . $brandName . ' products online.',
                ]
            );
        }
    }
}

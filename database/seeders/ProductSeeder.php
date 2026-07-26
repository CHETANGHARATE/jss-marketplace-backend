<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\ProductTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds for exactly 80 products (4 per category across 20 categories).
     */
    public function run(): void
    {
        $vendors = User::where('role', 'seller')->get();
        if ($vendors->isEmpty()) {
            $fallbackSellerId = User::first()?->id ?? 1;
        } else {
            $fallbackSellerId = $vendors->first()->id;
        }

        // Helper to pick vendor cyclic
        $getVendorId = function($index) use ($vendors, $fallbackSellerId) {
            if ($vendors->isEmpty()) return $fallbackSellerId;
            return $vendors[$index % $vendors->count()]->id;
        };

        // 20 Categories x 4 products each = 80 products
        $catalog = [
            // Category 1: juices_syrups
            'juices_syrups' => [
                ['name' => 'Organic Pure Amla Juice 1L', 'brand' => 'Dabur Nature', 'price' => 299, 'offer' => 249, 'img' => 'https://images.unsplash.com/photo-1622543925917-763c64d23efa?w=800&auto=format&fit=crop&q=80', 'desc' => '100% Cold pressed natural Amla juice rich in Vitamin C and antioxidants.'],
                ['name' => 'Kesar Mango Panna Concentrate 750ml', 'brand' => 'Mapro Foods', 'price' => 350, 'offer' => 299, 'img' => 'https://images.unsplash.com/photo-1546173159-315724a31696?w=800&auto=format&fit=crop&q=80', 'desc' => 'Traditional raw mango summer cooler syrup made with authentic spices.'],
                ['name' => 'Pure Rose Petal Syrup 500ml', 'brand' => 'Hamdard Rooh', 'price' => 220, 'offer' => 180, 'img' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=800&auto=format&fit=crop&q=80', 'desc' => 'Aromatic rose syrup for refreshing faloodas, milkshakes, and sherbets.'],
                ['name' => 'Wild Jamun Herbal Juice 1L', 'brand' => 'Real Fruit', 'price' => 380, 'offer' => 310, 'img' => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=800&auto=format&fit=crop&q=80', 'desc' => 'Natural unpasteurized Jamun juice helpful for diabetes and digestion.'],
            ],

            // Category 2: religious_pooja_items
            'religious_pooja_items' => [
                ['name' => 'Traditional Pure Brass Oil Diya Set of 2', 'brand' => 'Panchamrut Divine', 'price' => 599, 'offer' => 449, 'img' => 'https://images.unsplash.com/photo-1605379399642-870262d3d051?w=800&auto=format&fit=crop&q=80', 'desc' => 'Handcrafted solid brass oil lamps for daily pooja and festive decoration.'],
                ['name' => 'Bhimseni Camphor Tablets 250g', 'brand' => 'Om Shanti Samagri', 'price' => 450, 'offer' => 350, 'img' => 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=800&auto=format&fit=crop&q=80', 'desc' => '100% pure organic edible Bhimseni camphor for positive vibes and hawan.'],
                ['name' => 'Natural Chandan Dhoop Sticks Pack of 4', 'brand' => 'Cycle Pure Agarbatti', 'price' => 299, 'offer' => 220, 'img' => 'https://images.unsplash.com/photo-1602928321679-560bb453f190?w=800&auto=format&fit=crop&q=80', 'desc' => 'Charcoal-free sandalwood incense sticks with soothing long-lasting fragrance.'],
                ['name' => 'Handcarved Copper Kalash Lota', 'brand' => 'Pitambari Care', 'price' => 899, 'offer' => 699, 'img' => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=800&auto=format&fit=crop&q=80', 'desc' => 'Heavy gauge pure copper kalash vessel for temple rituals and holy water.'],
            ],

            // Category 3: cosmetics
            'cosmetics' => [
                ['name' => 'Kumkumadi Radiance Face Cream 50g', 'brand' => 'Forest Essentials', 'price' => 1250, 'offer' => 999, 'img' => 'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?w=800&auto=format&fit=crop&q=80', 'desc' => 'Pure saffron infused Ayurvedic moisturizing cream for youthful glowing skin.'],
                ['name' => 'Ayurvedic Bio Color Lip Balm', 'brand' => 'Biotique Botanicals', 'price' => 249, 'offer' => 199, 'img' => 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=800&auto=format&fit=crop&q=80', 'desc' => 'Organic tinted lip balm with natural almond oil and wildberry extract.'],
                ['name' => 'Pure Steam Distilled Rose Water Toner', 'brand' => 'Lotus Herbals', 'price' => 399, 'offer' => 299, 'img' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&auto=format&fit=crop&q=80', 'desc' => '100% natural Kannauj rose water toner for skin hydration and tightening.'],
                ['name' => 'Herbal Vitamin C Skin Brightening Serum', 'brand' => 'Lakme India', 'price' => 899, 'offer' => 649, 'img' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=800&auto=format&fit=crop&q=80', 'desc' => 'Potent herbal serum targeting dark spots and uneven skin tone.'],
            ],

            // Category 4: beauty_personal_care
            'beauty_personal_care' => [
                ['name' => 'Purifying Neem Face Wash 200ml', 'brand' => 'Himalaya Herbals', 'price' => 299, 'offer' => 239, 'img' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800&auto=format&fit=crop&q=80', 'desc' => 'Herbal cleanser formulated with neem and turmeric to prevent acne.'],
                ['name' => 'Organic Bhringraj Hair Growth Oil 200ml', 'brand' => 'Patanjali Ayurved', 'price' => 350, 'offer' => 280, 'img' => 'https://images.unsplash.com/photo-1608248597262-8382393a525f?w=800&auto=format&fit=crop&q=80', 'desc' => 'Cold pressed sesame oil base infused with 18 rare Ayurvedic herbs.'],
                ['name' => 'Pure Aloe Vera Skin Repair Gel 250g', 'brand' => 'Mamaearth Organic', 'price' => 399, 'offer' => 319, 'img' => 'https://images.unsplash.com/photo-1567928269937-ae81665a3f3b?w=800&auto=format&fit=crop&q=80', 'desc' => 'Multi-purpose 99% pure aloe vera gel for soothing sun-exposed skin.'],
                ['name' => 'Deep Moisture Body Lotion 400ml', 'brand' => 'Nivea Care', 'price' => 499, 'offer' => 399, 'img' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800&auto=format&fit=crop&q=80', 'desc' => 'Nourishing body lotion providing 48-hour intense hydration.'],
            ],

            // Category 5: footwear
            'footwear' => [
                ['name' => 'Handcrafted Authentic Kolhapuri Chappal', 'brand' => 'Kolhapuri Craft', 'price' => 1899, 'offer' => 1499, 'img' => 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=800&auto=format&fit=crop&q=80', 'desc' => '100% genuine buffalo leather hand-stitched traditional Kolhapuri footwear.'],
                ['name' => 'Men Genuine Leather Formals', 'brand' => 'Bata India', 'price' => 2999, 'offer' => 2299, 'img' => 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?w=800&auto=format&fit=crop&q=80', 'desc' => 'Classic Oxford style formal shoes with cushioned memory foam insoles.'],
                ['name' => 'Nike Revolution Lightweight Running Shoes', 'brand' => 'Nike Sports', 'price' => 4999, 'offer' => 3799, 'img' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&auto=format&fit=crop&q=80', 'desc' => 'Breathable mesh upper running shoes built for maximum speed and comfort.'],
                ['name' => 'Adidas Grand Court Mens Sneakers', 'brand' => 'Adidas Athletic', 'price' => 5499, 'offer' => 4199, 'img' => 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=800&auto=format&fit=crop&q=80', 'desc' => 'Iconic 3-stripe casual sneakers crafted with durable synthetic leather.'],
            ],

            // Category 6: pickles
            'pickles' => [
                ['name' => 'Traditional Spicy Mango Pickle 500g', 'brand' => 'Mother\'s Recipe', 'price' => 199, 'offer' => 159, 'img' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=800&auto=format&fit=crop&q=80', 'desc' => 'Authentic home-style raw mango pickle cured in pure mustard oil.'],
                ['name' => 'Special Kolhapuri Garlic Pickle 400g', 'brand' => 'Bedekar Pickles', 'price' => 220, 'offer' => 175, 'img' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?w=800&auto=format&fit=crop&q=80', 'desc' => 'Fiery garlic cloves pickled with Maharashtrian spices and sesame oil.'],
                ['name' => 'Sour & Sweet Lemon Chilly Pickle 500g', 'brand' => 'Pravin Spices', 'price' => 180, 'offer' => 140, 'img' => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=800&auto=format&fit=crop&q=80', 'desc' => 'Tangy lime and green chili mix marinated with jaggery and spices.'],
                ['name' => 'Guntur Red Chilli Pickle 300g', 'brand' => 'Priya Foods', 'price' => 210, 'offer' => 165, 'img' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=800&auto=format&fit=crop&q=80', 'desc' => 'Authentic South Indian spicy red chili pickle with tamarind twist.'],
            ],

            // Category 7: masale_spices
            'masale_spices' => [
                ['name' => 'Authentic Kanda Lasun Masala 500g', 'brand' => 'Everest Masale', 'price' => 280, 'offer' => 229, 'img' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=800&auto=format&fit=crop&q=80', 'desc' => 'Traditional onion-garlic red chili blend for authentic curries and rassa.'],
                ['name' => 'Pure Organic Turmeric Haldi Powder 500g', 'brand' => 'Tata Sampann', 'price' => 240, 'offer' => 195, 'img' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=800&auto=format&fit=crop&q=80', 'desc' => 'High curcumin content unadulterated turmeric powder from Sangli.'],
                ['name' => 'Royal Shahi Garam Masala 200g', 'brand' => 'MDH Spices', 'price' => 190, 'offer' => 149, 'img' => 'https://images.unsplash.com/photo-1509358271058-acd02cc93898?w=800&auto=format&fit=crop&q=80', 'desc' => 'Aromatic blend of 15 roasted whole spices for rich gravy preparations.'],
                ['name' => 'Kashmiri Red Chilli Powder 250g', 'brand' => 'Badshah Masala', 'price' => 260, 'offer' => 210, 'img' => 'https://images.unsplash.com/photo-1599940824399-b87987ceb72a?w=800&auto=format&fit=crop&q=80', 'desc' => 'Vibrant natural red color with mild pungency for perfect curries.'],
            ],

            // Category 8: fashion
            'fashion' => [
                ['name' => 'Handloom Paithani Silk Saree', 'brand' => 'FabIndia Ethnic', 'price' => 12999, 'offer' => 9999, 'img' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=800&auto=format&fit=crop&q=80', 'desc' => 'Pure silk traditional Yeola Paithani saree with peacock zari pallu.'],
                ['name' => 'Men Silk Blend Festive Kurta Set', 'brand' => 'Manyavar', 'price' => 3999, 'offer' => 2999, 'img' => 'https://images.unsplash.com/photo-1597983073493-88cd35cf03b0?w=800&auto=format&fit=crop&q=80', 'desc' => 'Elegant mandarin collar silk blend designer kurta pajama for celebrations.'],
                ['name' => 'Men Tailored Fit Formal Shirt', 'brand' => 'Raymond Fine', 'price' => 2499, 'offer' => 1899, 'img' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=800&auto=format&fit=crop&q=80', 'desc' => '100% Giza cotton premium breathable shirt for corporate office wear.'],
                ['name' => 'Men 511 Slim Fit Denim Jeans', 'brand' => 'Levi\'s Denim', 'price' => 4599, 'offer' => 3299, 'img' => 'https://images.unsplash.com/photo-1542272604-780c36856d62?w=800&auto=format&fit=crop&q=80', 'desc' => 'Classic 5-pocket indigo stretch denim engineered for comfort.'],
            ],

            // Category 9: jewellery
            'jewellery' => [
                ['name' => '22K Gold Plated Kundan Choker Necklace Set', 'brand' => 'Tanishq Gold', 'price' => 8999, 'offer' => 6499, 'img' => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=800&auto=format&fit=crop&q=80', 'desc' => 'Intricate bridal Kundan choker set embellished with pearls and gems.'],
                ['name' => 'Temple Antique Gold Earrings', 'brand' => 'Malabar Gold', 'price' => 3499, 'offer' => 2699, 'img' => 'https://images.unsplash.com/photo-1630019852942-f89202989a59?w=800&auto=format&fit=crop&q=80', 'desc' => 'Traditional Lakshmi idol engraved antique gold jhumka earrings.'],
                ['name' => 'Solid 925 Sterling Silver Payal Anklet Pair', 'brand' => 'Kalyan Jewellers', 'price' => 2199, 'offer' => 1699, 'img' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=800&auto=format&fit=crop&q=80', 'desc' => 'Handcrafted pure silver ghungroo anklet set with hallmarked purity.'],
                ['name' => 'Designer Pearl & Ruby Bangle Set', 'brand' => 'Senco Gold', 'price' => 2999, 'offer' => 2299, 'img' => 'https://images.unsplash.com/photo-1611591475281-9199d7990ef3?w=800&auto=format&fit=crop&q=80', 'desc' => 'Set of 4 traditional gold plated bangles studded with ruby gemstones.'],
            ],

            // Category 10: agriculture
            'agriculture' => [
                ['name' => 'Hybrid High Yield Tomato Seeds 100g', 'brand' => 'Syngenta Seeds', 'price' => 499, 'offer' => 399, 'img' => 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=800&auto=format&fit=crop&q=80', 'desc' => 'Disease-resistant high germination hybrid tomato seeds for farming.'],
                ['name' => 'Organic Bio-Fertilizer Vermicompost 10kg', 'brand' => 'UPL Agriculture', 'price' => 699, 'offer' => 549, 'img' => 'https://images.unsplash.com/photo-1585314062340-f1a5a7c9328d?w=800&auto=format&fit=crop&q=80', 'desc' => 'Nutrient-dense earthworm compost for enriched soil aeration and root growth.'],
                ['name' => 'Heavy Duty Farm Drip Irrigation Hose Pipe 100m', 'brand' => 'Nuziveedu Seeds', 'price' => 1899, 'offer' => 1499, 'img' => 'https://images.unsplash.com/photo-1563514227147-6d2ff665a6a0?w=800&auto=format&fit=crop&q=80', 'desc' => 'UV stabilized 16mm drip lateral line for efficient farm water delivery.'],
                ['name' => 'Cold Pressed Organic Neem Oil Spray 1L', 'brand' => 'Bayer Crop', 'price' => 450, 'offer' => 349, 'img' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=800&auto=format&fit=crop&q=80', 'desc' => 'Natural bio-pesticide and insect repellent for crops and home gardens.'],
            ],

            // Category 11: auto_accessories
            'auto_accessories' => [
                ['name' => 'Ergonomic Memory Foam Car Seat Cushion', 'brand' => 'AutoForm Covers', 'price' => 1499, 'offer' => 1099, 'img' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&auto=format&fit=crop&q=80', 'desc' => 'Breathable lumbar support cushion for long driving posture comfort.'],
                ['name' => 'Heavy Waterproof All-Weather Bike Cover', 'brand' => 'Steelbird Helmets', 'price' => 799, 'offer' => 599, 'img' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=800&auto=format&fit=crop&q=80', 'desc' => 'Dustproof UV-protected two-wheeler cover with buckle lock straps.'],
                ['name' => 'Bosch Portable High Pressure Car Washer', 'brand' => 'Bosch Auto', 'price' => 7999, 'offer' => 5999, 'img' => 'https://images.unsplash.com/photo-1520340356584-f9917d1eea6f?w=800&auto=format&fit=crop&q=80', 'desc' => '120 Bar pressure washer for car, bike, and patio surface deep cleaning.'],
                ['name' => 'Digital Car Tyre Inflator Air Compressor', 'brand' => 'Sparco Racing', 'price' => 2499, 'offer' => 1899, 'img' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=800&auto=format&fit=crop&q=80', 'desc' => '12V DC heavy duty portable air pump with auto shutoff gauge.'],
            ],

            // Category 12: local_homemade
            'local_homemade' => [
                ['name' => 'Pure Gir Cow A2 Desi Ghee 1L', 'brand' => 'Desi Ghee Co', 'price' => 1899, 'offer' => 1499, 'img' => 'https://images.unsplash.com/photo-1628102491629-778571d893a3?w=800&auto=format&fit=crop&q=80', 'desc' => 'Traditional bilona method cultured Ghee made from grass-fed A2 Gir cows.'],
                ['name' => 'Special Homemade Poha Chivda 500g', 'brand' => 'Chitale Bandhu', 'price' => 240, 'offer' => 195, 'img' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=800&auto=format&fit=crop&q=80', 'desc' => 'Crispy thin flattened rice chivda fried with cashews, peanuts, and spices.'],
                ['name' => 'Organic Organic Sugarcane Jaggery Gud 1kg', 'brand' => 'Sattvic Foods', 'price' => 220, 'offer' => 170, 'img' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=800&auto=format&fit=crop&q=80', 'desc' => 'Chemical-free unrefined natural jaggery blocks packed with iron.'],
                ['name' => 'Handmade Goat Milk & Honey Soap 3x100g', 'brand' => 'Suhana Flavors', 'price' => 399, 'offer' => 299, 'img' => 'https://images.unsplash.com/photo-1607006482172-32617300c732?w=800&auto=format&fit=crop&q=80', 'desc' => 'Artisanal cold process moisturizing soap bar for sensitive skin.'],
            ],

            // Category 13: pooja_spiritual
            'pooja_spiritual' => [
                ['name' => 'Authentic 5-Mukhi Rudraksha Mala 108 Beads', 'brand' => 'Rudra Centre', 'price' => 1299, 'offer' => 899, 'img' => 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=800&auto=format&fit=crop&q=80', 'desc' => 'Lab certified natural Nepal 5 mukhi rudraksha rosary for meditation.'],
                ['name' => 'Brass Temple Hanging Ghanti Bell', 'brand' => 'Divine Crafts', 'price' => 1499, 'offer' => 1099, 'img' => 'https://images.unsplash.com/photo-1605379399642-870262d3d051?w=800&auto=format&fit=crop&q=80', 'desc' => 'Heavy resonant brass bell with carved Garuda finial for mandir.'],
                ['name' => 'Pure Mysore Sandalwood Paste Paste 100g', 'brand' => 'Giri Trading', 'price' => 450, 'offer' => 349, 'img' => 'https://images.unsplash.com/photo-1602928321679-560bb453f190?w=800&auto=format&fit=crop&q=80', 'desc' => 'Pure chandan paste prepared for tilak and idol abhishekam.'],
                ['name' => 'Pancharti Brass Aarti Thali Plate', 'brand' => 'Astha Spiritual', 'price' => 899, 'offer' => 699, 'img' => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=800&auto=format&fit=crop&q=80', 'desc' => 'Complete ritual brass plate with built-in 5-faced oil lamps.'],
            ],

            // Category 14: gifts_handicrafts
            'gifts_handicrafts' => [
                ['name' => 'Handcrafted Wooden Marble Inlay Jewellery Box', 'brand' => 'Rajasthan Artisans', 'price' => 2499, 'offer' => 1899, 'img' => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=800&auto=format&fit=crop&q=80', 'desc' => 'Royal Rajasthani wooden box inlaid with semi-precious floral stones.'],
                ['name' => 'Solid Brass Dancing Peacock Statue', 'brand' => 'Cottage Industries', 'price' => 3499, 'offer' => 2699, 'img' => 'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=800&auto=format&fit=crop&q=80', 'desc' => 'Intricately carved brass peacock idol for home decor showcase.'],
                ['name' => 'Terracotta Wall Hanging Diya Panel', 'brand' => 'Terracotta India', 'price' => 1199, 'offer' => 899, 'img' => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=800&auto=format&fit=crop&q=80', 'desc' => 'Handmade fired clay mural plaque painted by rural artisans.'],
                ['name' => 'Eco-Friendly Bamboo Flower Vase Set', 'brand' => 'Channapatna Toys', 'price' => 899, 'offer' => 649, 'img' => 'https://images.unsplash.com/photo-1581783342308-f792dbdd27c5?w=800&auto=format&fit=crop&q=80', 'desc' => 'Sustainable polished natural bamboo desktop flower vase.'],
            ],

            // Category 15: baby_kids
            'baby_kids' => [
                ['name' => 'Organic Cotton Baby Romper Onesie Pack of 3', 'brand' => 'FirstCry Kids', 'price' => 1199, 'offer' => 899, 'img' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=800&auto=format&fit=crop&q=80', 'desc' => 'Ultra soft breathable 100% GOTS certified cotton baby bodysuits.'],
                ['name' => 'Nourishing Herbal Baby Massage Oil 200ml', 'brand' => 'Johnson & Baby', 'price' => 399, 'offer' => 319, 'img' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=800&auto=format&fit=crop&q=80', 'desc' => 'Dermatologically tested virgin olive and sesame oil blend for soft skin.'],
                ['name' => 'Non-Toxic Wooden Educational Stacking Blocks', 'brand' => 'Chicco Care', 'price' => 899, 'offer' => 649, 'img' => 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?w=800&auto=format&fit=crop&q=80', 'desc' => 'Child-safe vegetable dye painted wooden geometry puzzle blocks.'],
                ['name me' => 'Tear-Free Baby Head-To-Toe Body Wash', 'brand' => 'Sebamed Baby', 'price' => 699, 'offer' => 549, 'img' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&auto=format&fit=crop&q=80', 'desc' => 'pH 5.5 balanced soap-free baby wash for delicate sensitive skin.'],
            ],

            // Category 16: oil
            'oil' => [
                ['name' => 'Cold Pressed Kachi Ghani Mustard Oil 1L', 'brand' => 'Fortune Oil', 'price' => 240, 'offer' => 195, 'img' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=800&auto=format&fit=crop&q=80', 'desc' => 'Pungent raw cold pressed mustard oil rich in Omega-3 fatty acids.'],
                ['name' => 'Filtered Pure Groundnut Peanut Oil 1L', 'brand' => 'Dhara Pure', 'price' => 260, 'offer' => 210, 'img' => 'https://images.unsplash.com/photo-1620706857370-e1b9770e8bb1?w=800&auto=format&fit=crop&q=80', 'desc' => 'High smoke point traditional groundnut oil ideal for deep frying.'],
                ['name' => 'Extra Virgin Cold Pressed Coconut Oil 500ml', 'brand' => 'Saffola Gold', 'price' => 399, 'offer' => 319, 'img' => 'https://images.unsplash.com/photo-1608248597262-8382393a525f?w=800&auto=format&fit=crop&q=80', 'desc' => 'Raw unrefined virgin coconut oil for healthy cooking and hair care.'],
                ['name' => 'Organic Sesame Til Gingelly Oil 1L', 'brand' => 'Gemini Oil', 'price' => 380, 'offer' => 299, 'img' => 'https://images.unsplash.com/photo-1628102491629-778571d893a3?w=800&auto=format&fit=crop&q=80', 'desc' => 'Aromatic cold pressed white sesame seed oil for traditional dishes.'],
            ],

            // Category 17: papad_kurdai
            'papad_kurdai' => [
                ['name' => 'Special Urad Dal Garlic Papad 500g', 'brand' => 'Lijjat Papad', 'price' => 180, 'offer' => 140, 'img' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=800&auto=format&fit=crop&q=80', 'desc' => 'Crispy hand-rolled urad dal papad flavored with black pepper and garlic.'],
                ['name' => 'Traditional Wheat Kurdai Sundried 400g', 'brand' => 'Satara Kurdai', 'price' => 220, 'offer' => 175, 'img' => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=800&auto=format&fit=crop&q=80', 'desc' => 'Authentic fermented wheat string kurdai fryums ready for oil frying.'],
                ['name' => 'Rice Flour Spiced Chakli 400g', 'brand' => 'Mahila Griha Udyog', 'price' => 199, 'offer' => 159, 'img' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?w=800&auto=format&fit=crop&q=80', 'desc' => 'Crunchy fried spiral savory chakli made from roasted rice flour.'],
                ['name' => 'Sabudana Batata Fasting Papad 300g', 'brand' => 'Mother Special Papad', 'price' => 160, 'offer' => 125, 'img' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=800&auto=format&fit=crop&q=80', 'desc' => 'Sago and potato wafers specially seasoned for upvas and fasts.'],
            ],

            // Category 18: astro_stone
            'astro_stone' => [
                ['name' => 'Natural Certified Yellow Sapphire Pukhraj 5.25 Ratti', 'brand' => 'Ratna Gems', 'price' => 14999, 'offer' => 11999, 'img' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=800&auto=format&fit=crop&q=80', 'desc' => 'Untreated unheated Ceylon yellow sapphire gemstone with lab certificate.'],
                ['name' => 'Natural Blue Sapphire Neelam 4.5 Ratti', 'brand' => 'GemPundit Certified', 'price' => 18999, 'offer' => 14999, 'img' => 'https://images.unsplash.com/photo-1611591475281-9199d7990ef3?w=800&auto=format&fit=crop&q=80', 'desc' => 'Vivid blue original Kashmir sapphire stone for Saturn alignment.'],
                ['name' => 'Italian Red Coral Moonga Stone 6 Ratti', 'brand' => 'AstroVed Stones', 'price' => 6999, 'offer' => 5299, 'img' => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=800&auto=format&fit=crop&q=80', 'desc' => 'Capsule shape deep red natural Italian coral for Mars energy.'],
                ['name' => 'Zambian Emerald Panna Gemstone 5 Ratti', 'brand' => 'Precious Gems House', 'price' => 12999, 'offer' => 9999, 'img' => 'https://images.unsplash.com/photo-1630019852942-f89202989a59?w=800&auto=format&fit=crop&q=80', 'desc' => 'High clarity natural green emerald for intellect and Mercury benefits.'],
            ],

            // Category 19: diwali_faral
            'diwali_faral' => [
                ['name' => 'Pure Ghee Shahi Besan Ladoo 500g', 'brand' => 'Chitale Faral', 'price' => 380, 'offer' => 299, 'img' => 'https://images.unsplash.com/photo-1541781774459-bb2af2f05b55?w=800&auto=format&fit=crop&q=80', 'desc' => 'Melt-in-mouth gram flour sweets roasted in pure cow ghee with dry fruits.'],
                ['name' => 'Crispy Maharashtrian Bhakarwadi 400g', 'brand' => 'Kaka Halwai', 'price' => 240, 'offer' => 195, 'img' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=800&auto=format&fit=crop&q=80', 'desc' => 'Spicy and sweet fried pinwheel snack stuffed with coconut and poppy seeds.'],
                ['name' => 'Special Dagdi Poha Chivda 500g', 'brand' => 'Laxmi Narayan Chivda', 'price' => 260, 'offer' => 210, 'img' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?w=800&auto=format&fit=crop&q=80', 'desc' => 'World-famous Pune Laxmi Narayan special thin poha savory chivda.'],
                ['name' => 'Sweet Crispy Shakarpara 400g', 'brand' => 'MMB Sweets', 'price' => 220, 'offer' => 175, 'img' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=800&auto=format&fit=crop&q=80', 'desc' => 'Diamond cut traditional maida and sugar crunchy festive sweets.'],
            ],

            // Category 20: electronics
            'electronics' => [
                ['name' => 'Samsung 43-inch Crystal 4K Smart LED TV', 'brand' => 'Samsung Electronics', 'price' => 34999, 'offer' => 27999, 'img' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=800&auto=format&fit=crop&q=80', 'desc' => 'Ultra HD Smart TV with HDR10+, Dolby Audio, and bezel-less design.'],
                ['name' => 'Apple AirPods Pro Wireless Earbuds', 'brand' => 'Apple Inc', 'price' => 24900, 'offer' => 19999, 'img' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80', 'desc' => 'Active noise cancellation with spatial audio and MagSafe charging case.'],
                ['name' => 'Boat Stone 50W Portable Bluetooth Speaker', 'brand' => 'Boat Lifestyle', 'price' => 4999, 'offer' => 2999, 'img' => 'https://images.unsplash.com/photo-1545454675-3531b543be5d?w=800&auto=format&fit=crop&q=80', 'desc' => 'IPX7 waterproof wireless party speaker with 12 hours playtime.'],
                ['name' => 'Sony Noise Cancelling Headphones WH-1000XM5', 'brand' => 'Sony Audio', 'price' => 29990, 'offer' => 24990, 'img' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=800&auto=format&fit=crop&q=80', 'desc' => 'Industry-leading noise cancelling over-ear headphones with 8 mics.'],
            ],
        ];

        $globalProductCounter = 1;

        foreach ($catalog as $catSlug => $items) {
            $category = Category::where('slug', $catSlug)->first();
            if (!$category) continue;

            foreach ($items as $itemIndex => $p) {
                $brand = Brand::where('name', 'LIKE', '%' . $p['brand'] . '%')->first();

                // Format SKU: e.g. JSS-PROD-001 ... JSS-PROD-080
                $sku = 'JSS-PROD-' . str_pad($globalProductCounter, 3, '0', STR_PAD_LEFT);
                $titleEn = $p['name'] ?? ('Product ' . $globalProductCounter);
                $slug = Str::slug($titleEn);

                $productData = [
                    'seller_id' => $getVendorId($globalProductCounter),
                    'category_id' => $category->id,
                    'subcategory_id' => null,
                    'brand_id' => $brand?->id,
                    'sku' => $sku,
                    'name' => [
                        'en' => $titleEn,
                        'hi' => $titleEn,
                        'mr' => $titleEn,
                    ],
                    'slug' => $slug,
                    'short_description' => [
                        'en' => $p['desc'],
                    ],
                    'description' => [
                        'en' => $p['desc'] . ' Premium quality certified product available for direct express shipping across India.',
                    ],
                    'thumbnail' => $p['img'],
                    'original_price' => $p['price'],
                    'offer_price' => $p['offer'],
                    'stock_status' => 'in_stock',
                    'stock_quantity' => rand(25, 120),
                    'rating' => round(4.0 + (rand(0, 9) / 10), 1),
                    'reviews_count' => rand(15, 240),
                    'is_featured' => ($itemIndex % 2 === 0),
                    'is_trending' => ($itemIndex === 0 || $itemIndex === 3),
                    'is_active' => true,
                    'status' => 'approved',
                ];

                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    $productData
                );

                // Add 2 Gallery Images
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'image_url' => $p['img']],
                    ['is_primary' => true, 'sort_order' => 1]
                );
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'image_url' => $p['img']],
                    ['is_primary' => false, 'sort_order' => 2]
                );

                // Add 2 Specifications
                ProductSpecification::updateOrCreate(
                    ['product_id' => $product->id, 'spec_key' => 'Quality Standard'],
                    ['spec_value' => 'ISO & FSSAI Certified', 'sort_order' => 1]
                );
                ProductSpecification::updateOrCreate(
                    ['product_id' => $product->id, 'spec_key' => 'Country of Origin'],
                    ['spec_value' => 'India', 'sort_order' => 2]
                );

                // Add Tags
                $tagList = ['marketplace', $catSlug, strtolower(explode(' ', $titleEn)[0])];
                foreach ($tagList as $tStr) {
                    ProductTag::firstOrCreate(
                        ['product_id' => $product->id, 'tag' => strtolower($tStr)]
                    );
                }

                $globalProductCounter++;
            }
        }
    }
}

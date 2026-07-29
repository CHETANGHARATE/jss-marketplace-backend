<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds for all 20 parent categories and their subcategories.
     */
    public function run(): void
    {
        $categoriesData = [
            [
                'name' => ['en' => 'Juices & Syrups', 'hi' => 'जूस और सिरप', 'mr' => 'ज्यूस आणि सिरप'],
                'slug' => 'juices-syrups',
                'description' => ['en' => 'Natural fruit juices, herbal syrups, and wellness concentrates', 'hi' => 'प्राकृतिक फल जूस और हर्बल सिरप'],
                'icon' => 'CupSoda',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Fruit Juices', 'hi' => 'फलों का जूस'], 'slug' => 'fruit-juices'],
                    ['name' => ['en' => 'Herbal Syrups', 'hi' => 'हर्बल सिरप'], 'slug' => 'herbal-syrups'],
                    ['name' => ['en' => 'Concentrates & Squashes', 'hi' => 'कंसंट्रेट्स और स्क्वैश'], 'slug' => 'concentrates-squashes'],
                    ['name' => ['en' => 'Ayurvedic Health Drinks', 'hi' => 'आयुर्वेदिक हेल्थ ड्रिंक्स'], 'slug' => 'ayurvedic-health-drinks'],
                    ['name' => ['en' => 'Energy & Wellness Drinks', 'hi' => 'एनर्जी ड्रिंक्स'], 'slug' => 'energy-wellness-drinks'],
                    ['name' => ['en' => 'Organic Syrups', 'hi' => 'ऑर्गेनिक सिरप'], 'slug' => 'organic-syrups'],
                ]
            ],
            [
                'name' => ['en' => 'Religious & Pooja Items', 'hi' => 'धार्मिक एवं पूजा सामग्री', 'mr' => 'धार्मिक आणि पूजा साहित्य'],
                'slug' => 'religious-pooja-items',
                'description' => ['en' => 'Sacred pooja kits, agarbatti, brass lamps, and Hawan samagri', 'hi' => 'पूजा किट, अगरबत्ती, दीपक और हवन सामग्री'],
                'icon' => 'Flame',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Pooja Samagri Kits', 'hi' => 'पूजा सामग्री किट'], 'slug' => 'pooja-samagri-kits'],
                    ['name' => ['en' => 'Incense Sticks & Dhoop', 'hi' => 'अगरबत्ती और धूप'], 'slug' => 'incense-sticks-dhoop'],
                    ['name' => ['en' => 'Diya & Brass Oil Lamps', 'hi' => 'दीया और पीतल के दीपक'], 'slug' => 'diya-brass-oil-lamps'],
                    ['name' => ['en' => 'Camphor & Kapur', 'hi' => 'कपूर'], 'slug' => 'camphor-kapur'],
                    ['name' => ['en' => 'Idol Statues & Photo Frames', 'hi' => 'मूर्ति और फोटो फ्रेम'], 'slug' => 'idol-statues-photo-frames'],
                    ['name' => ['en' => 'Hawan Samagri', 'hi' => 'हवन सामग्री'], 'slug' => 'hawan-samagri'],
                ]
            ],
            [
                'name' => ['en' => 'Cosmetics', 'hi' => 'कॉस्मेटिक्स', 'mr' => 'कॉस्मेटिक्स'],
                'slug' => 'cosmetics',
                'description' => ['en' => 'Makeup, lipsticks, eye cosmetics, and organic beauty products', 'hi' => 'मेकअप, लिपस्टिक, आई मेकअप और ऑर्गेनिक उत्पाद'],
                'icon' => 'Sparkle',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Face Makeup & Foundation', 'hi' => 'फेस मेकअप और फाउंडेशन'], 'slug' => 'face-makeup-foundation'],
                    ['name' => ['en' => 'Lipsticks & Lip Care', 'hi' => 'लिपस्टिक और लिप केयर'], 'slug' => 'lipsticks-lip-care'],
                    ['name' => ['en' => 'Eye Makeup & Kajal', 'hi' => 'आई मेकअप और काजल'], 'slug' => 'eye-makeup-kajal'],
                    ['name' => ['en' => 'Nail Care & Polish', 'hi' => 'नेल पोलिश'], 'slug' => 'nail-care-polish'],
                    ['name' => ['en' => 'Makeup Brushes & Tools', 'hi' => 'मेकअप ब्रश और टूल्स'], 'slug' => 'makeup-brushes-tools'],
                    ['name' => ['en' => 'Organic & Herbal Cosmetics', 'hi' => 'हर्बल कॉस्मेटिक्स'], 'slug' => 'organic-herbal-cosmetics'],
                ]
            ],
            [
                'name' => ['en' => 'Beauty & Personal Care', 'hi' => 'सौंदर्य और व्यक्तिगत देखभाल', 'mr' => 'सौंदर्य आणि वैयक्तिक काळजी'],
                'slug' => 'beauty-personal-care',
                'description' => ['en' => 'Skincare moisturizers, shampoos, body washes, and grooming kits', 'hi' => 'स्किनकेयर, शैम्पू, बॉडी वॉश और ग्रूमिंग किट'],
                'icon' => 'Heart',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Skincare & Moisturizers', 'hi' => 'स्किनकेयर और मॉइस्चराइज़र'], 'slug' => 'skincare-moisturizers'],
                    ['name' => ['en' => 'Hair Oils & Shampoos', 'hi' => 'हेयर ऑयल और शैम्पू'], 'slug' => 'hair-oils-shampoos'],
                    ['name' => ['en' => 'Soaps & Body Wash', 'hi' => 'साबुन और बॉडी वॉश'], 'slug' => 'soaps-body-wash'],
                    ['name' => ['en' => 'Face Wash & Cleansers', 'hi' => 'फेस वॉश और क्लींजर'], 'slug' => 'face-wash-cleansers'],
                    ['name' => ['en' => 'Oral Care & Toothpaste', 'hi' => 'ओरल केयर और टूथपेस्ट'], 'slug' => 'oral-care-toothpaste'],
                    ['name' => ['en' => "Men's Grooming & Shaving", 'hi' => 'मेन्स ग्रूमिंग और शेविंग'], 'slug' => 'mens-grooming-shaving'],
                ]
            ],
            [
                'name' => ['en' => 'Footwear', 'hi' => 'फुटवियर', 'mr' => 'फूटवेअर'],
                'slug' => 'footwear',
                'description' => ['en' => 'Men, women, and kids footwear, ethnic juttis, and sports shoes', 'hi' => 'पुरुषों, महिलाओं और बच्चों के जूते और चप्पल'],
                'icon' => 'Footprints',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => "Men's Casual & Formal Shoes", 'hi' => 'पुरुषों के जूते'], 'slug' => 'mens-shoes'],
                    ['name' => ['en' => "Women's Sandals & Heels", 'hi' => 'महिलाओं की सैंडल'], 'slug' => 'womens-sandals-heels'],
                    ['name' => ['en' => 'Ethnic Juttis & Kolhapuris', 'hi' => 'एथनिक जूती और कोल्हापुरी'], 'slug' => 'ethnic-juttis-kolhapuris'],
                    ['name' => ['en' => 'Sports & Running Shoes', 'hi' => 'स्पोर्ट्स जूते'], 'slug' => 'sports-running-shoes'],
                    ['name' => ['en' => 'Kids Footwear', 'hi' => 'बच्चों के जूते'], 'slug' => 'kids-footwear'],
                    ['name' => ['en' => 'Slippers & Flip Flops', 'hi' => 'स्लीपर और चप्पल'], 'slug' => 'slippers-flip-flops'],
                ]
            ],
            [
                'name' => ['en' => 'Pickles', 'hi' => 'अचार', 'mr' => 'लोणचे'],
                'slug' => 'pickles',
                'description' => ['en' => 'Authentic homemade mango, lemon, garlic, and regional pickles', 'hi' => 'आम, नींबू, मिर्च और लहसुन का पारंपरिक अचार'],
                'icon' => 'Utensils',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Mango Pickles (Aam Ka Achar)', 'hi' => 'आम का अचार'], 'slug' => 'mango-pickles'],
                    ['name' => ['en' => 'Lemon & Lime Pickles', 'hi' => 'नींबू का अचार'], 'slug' => 'lemon-lime-pickles'],
                    ['name' => ['en' => 'Chilli & Garlic Pickles', 'hi' => 'मिर्च और लहसुन अचार'], 'slug' => 'chilli-garlic-pickles'],
                    ['name' => ['en' => 'Mixed Veg Pickles', 'hi' => 'मिक्स वेज अचार'], 'slug' => 'mixed-veg-pickles'],
                    ['name' => ['en' => 'Non-Veg Pickles', 'hi' => 'नॉन-वेज अचार'], 'slug' => 'non-veg-pickles'],
                    ['name' => ['en' => 'Traditional Regional Pickles', 'hi' => 'पारंपरिक क्षेत्रीय अचार'], 'slug' => 'traditional-regional-pickles'],
                ]
            ],
            [
                'name' => ['en' => 'Masale', 'hi' => 'मसाले', 'mr' => 'मसाले'],
                'slug' => 'masale',
                'description' => ['en' => 'Whole spices, ground powders, garam masala, and regional curry blends', 'hi' => 'खड़े मसाले, पिसे मसाले और गरम मसाला'],
                'icon' => 'Wheat',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Whole Spices (Khadya Masala)', 'hi' => 'खड़े मसाले'], 'slug' => 'whole-spices'],
                    ['name' => ['en' => 'Ground Spice Powders', 'hi' => 'पिसे मसाले'], 'slug' => 'ground-spice-powders'],
                    ['name' => ['en' => 'Blended Garam Masala', 'hi' => 'गरम मसाला'], 'slug' => 'blended-garam-masala'],
                    ['name' => ['en' => 'Regional Curry Powders', 'hi' => 'क्षेत्रीय करी मसाले'], 'slug' => 'regional-curry-powders'],
                    ['name' => ['en' => 'Organic & Hand-Pounded Spices', 'hi' => 'ऑर्गेनिक मसाले'], 'slug' => 'organic-hand-pounded-spices'],
                    ['name' => ['en' => 'Biryani & Chole Masala', 'hi' => 'बिरयानी और छोले मसाले'], 'slug' => 'biryani-chole-masala'],
                ]
            ],
            [
                'name' => ['en' => 'Fashion', 'hi' => 'फैशन', 'mr' => 'फॅशन'],
                'slug' => 'fashion',
                'description' => ['en' => 'Men, women, and kids apparel, ethnic wear, and fashion accessories', 'hi' => 'पुरुषों, महिलाओं और बच्चों के कपड़े और एथनिक वियर'],
                'icon' => 'Shirt',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => "Men's Wear", 'hi' => 'पुरुषों के कपड़े'], 'slug' => 'mens-wear'],
                    ['name' => ['en' => "Women's Wear", 'hi' => 'महिलाओं के कपड़े'], 'slug' => 'womens-wear'],
                    ['name' => ['en' => 'Ethnic Wear & Sarees', 'hi' => 'एथनिक वियर और साड़ी'], 'slug' => 'ethnic-wear-sarees'],
                    ['name' => ['en' => 'Kids Wear', 'hi' => 'बच्चों के कपड़े'], 'slug' => 'kids-wear'],
                    ['name' => ['en' => 'Fashion Accessories', 'hi' => 'फैशन एक्सेसरीज'], 'slug' => 'fashion-accessories'],
                    ['name' => ['en' => 'Winter & Seasonal Wear', 'hi' => 'विंटर वियर'], 'slug' => 'winter-seasonal-wear'],
                ]
            ],
            [
                'name' => ['en' => 'Jewellery', 'hi' => 'आभूषण और ज्वेलरी', 'mr' => 'दागिने व ज्वेलरी'],
                'slug' => 'jewellery',
                'description' => ['en' => 'Gold, silver, artificial fashion jewellery, and bridal sets', 'hi' => 'सोने, चांदी और कृत्रिम आभूषण'],
                'icon' => 'Gem',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Gold Jewellery', 'hi' => 'गोल्ड ज्वेलरी'], 'slug' => 'gold-jewellery'],
                    ['name' => ['en' => 'Silver Jewellery', 'hi' => 'सिल्वर ज्वेलरी'], 'slug' => 'silver-jewellery'],
                    ['name' => ['en' => 'Artificial & Fashion Jewellery', 'hi' => 'आर्टिफिशियल ज्वेलरी'], 'slug' => 'artificial-fashion-jewellery'],
                    ['name' => ['en' => 'Bridal Jewellery Sets', 'hi' => 'ब्राइडल ज्वेलरी'], 'slug' => 'bridal-jewellery-sets'],
                    ['name' => ['en' => 'Temple Jewellery', 'hi' => 'टेंपल ज्वेलरी'], 'slug' => 'temple-jewellery'],
                    ['name' => ['en' => 'Gemstone & Beaded Jewellery', 'hi' => 'नगीनेदार आभूषण'], 'slug' => 'gemstone-beaded-jewellery'],
                ]
            ],
            [
                'name' => ['en' => 'Agriculture', 'hi' => 'कृषि और खेती', 'mr' => 'कृषी व शेती'],
                'slug' => 'agriculture',
                'description' => ['en' => 'High yield seeds, bio fertilizers, pesticides, and farm tools', 'hi' => 'बीज, जैविक उर्वरक, कीटनाशक और कृषि उपकरण'],
                'icon' => 'Sprout',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'High Yield Seeds', 'hi' => 'उच्च उपज वाले बीज'], 'slug' => 'high-yield-seeds'],
                    ['name' => ['en' => 'Bio Fertilizers & Compost', 'hi' => 'जैविक खाद और उर्वरक'], 'slug' => 'bio-fertilizers-compost'],
                    ['name' => ['en' => 'Organic Insecticides & Pesticides', 'hi' => 'जैविक कीटनाशक'], 'slug' => 'organic-pesticides'],
                    ['name' => ['en' => 'Farm Hand Tools & Equipment', 'hi' => 'कृषि उपकरण'], 'slug' => 'farm-tools-equipment'],
                    ['name' => ['en' => 'Drip Irrigation Kits', 'hi' => 'ड्रिप सिंचाई किट'], 'slug' => 'drip-irrigation-kits'],
                    ['name' => ['en' => 'Plant Care & Gardening', 'hi' => 'पौधों की देखभाल'], 'slug' => 'plant-care-gardening'],
                ]
            ],
            [
                'name' => ['en' => 'Auto Accessories', 'hi' => 'ऑटो एक्सेसरीज', 'mr' => 'ऑटो ॲक्सेसरीज'],
                'slug' => 'auto-accessories',
                'description' => ['en' => 'Car cleaning gear, helmets, seat covers, and bike accessories', 'hi' => 'कार और बाइक के सामान, हेलमेट और सीट कवर'],
                'icon' => 'Car',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Car Cleaning & Care', 'hi' => 'कार क्लीनिंग प्रोडक्ट्स'], 'slug' => 'car-cleaning-care'],
                    ['name' => ['en' => 'Helmet & Riding Gear', 'hi' => 'हेलमेट और राइडिंग गियर'], 'slug' => 'helmet-riding-gear'],
                    ['name' => ['en' => 'Car Seat Covers & Mats', 'hi' => 'सीट कवर और मैट्स'], 'slug' => 'car-seat-covers-mats'],
                    ['name' => ['en' => 'Bike Accessories & Covers', 'hi' => 'बाइक एक्सेसरीज'], 'slug' => 'bike-accessories-covers'],
                    ['name' => ['en' => 'Mobile Holders & Chargers', 'hi' => 'मोबाइल होल्डर और चार्जर'], 'slug' => 'mobile-holders-chargers'],
                    ['name' => ['en' => 'Automotive LED Lights', 'hi' => 'ऑटोमोटिव एलईडी लाइट्स'], 'slug' => 'automotive-led-lights'],
                ]
            ],
            [
                'name' => ['en' => 'Local & Homemade Products', 'hi' => 'स्थानीय एवं घरेलू उत्पाद', 'mr' => 'स्थानिक व घरगुती उत्पादने'],
                'slug' => 'local-homemade-products',
                'description' => ['en' => 'Artisan handicrafts, pure ghee, homemade snacks, and traditional sweets', 'hi' => 'हस्तशिल्प, शुद्ध घी, घरेलू नमकीन और पारंपरिक मिठाइयां'],
                'icon' => 'Home',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Handmade Snacks & Khakhra', 'hi' => 'घरेलू खाखरा और नमकीन'], 'slug' => 'handmade-snacks-khakhra'],
                    ['name' => ['en' => 'Homemade Ghee & Butter', 'hi' => 'घर का बना घी और मक्खन'], 'slug' => 'homemade-ghee-butter'],
                    ['name' => ['en' => 'Artisan Craft & Decor', 'hi' => 'हस्तशिल्प और कला'], 'slug' => 'artisan-craft-decor'],
                    ['name' => ['en' => 'Handmade Soaps & Candles', 'hi' => 'हैंडमेड साबुन और मोमबत्तियां'], 'slug' => 'handmade-soaps-candles'],
                    ['name' => ['en' => 'Homemade Jams & Preserves', 'hi' => 'घरेलू जैम और सॉस'], 'slug' => 'homemade-jams-preserves'],
                    ['name' => ['en' => 'Traditional Sweets', 'hi' => 'पारंपरिक मिठाइयां'], 'slug' => 'traditional-sweets'],
                ]
            ],
            [
                'name' => ['en' => 'Pooja & Spiritual', 'hi' => 'पूजा और आध्यात्मिक', 'mr' => 'पूजा आणि अध्यात्मिक'],
                'slug' => 'pooja-spiritual',
                'description' => ['en' => 'Rudraksha beads, gemstones, chanting mala, brass bells, and idols', 'hi' => 'रुद्राक्ष, माला, पीतल की घंटी, शंख और आध्यात्मिक पुस्तकें'],
                'icon' => 'Sun',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Rudraksha & Mala Beads', 'hi' => 'रुद्राक्ष और जप माला'], 'slug' => 'rudraksha-mala-beads'],
                    ['name' => ['en' => 'Gemstones & Yantras', 'hi' => 'यंत्र और रत्न'], 'slug' => 'gemstones-yantras'],
                    ['name' => ['en' => 'Spiritual Books & Chanting Beads', 'hi' => 'धार्मिक पुस्तकें'], 'slug' => 'spiritual-books-beads'],
                    ['name' => ['en' => 'Temple Brass Bell & Shankh', 'hi' => 'पीतल की घंटी और शंख'], 'slug' => 'temple-brass-bell-shankh'],
                    ['name' => ['en' => 'Gangajal & Holy Water', 'hi' => 'गंगाजल और पवित्र जल'], 'slug' => 'gangajal-holy-water'],
                    ['name' => ['en' => 'Pooja Thali Sets', 'hi' => 'पूजा थाली सेट'], 'slug' => 'pooja-thali-sets'],
                ]
            ],
            [
                'name' => ['en' => 'Gifts & Handicrafts', 'hi' => 'उपहार एवं हस्तशिल्प', 'mr' => 'भेटवस्तू व हस्तकला'],
                'slug' => 'gifts-handicrafts',
                'description' => ['en' => 'Wooden crafts, brass idols, festive hampers, and custom photo gifts', 'hi' => 'लकड़ी के हस्तशिल्प, पीतल की मूर्तियां और त्यौहार के गिफ्ट'],
                'icon' => 'Gift',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Wooden Handicrafts', 'hi' => 'वुडन हस्तशिल्प'], 'slug' => 'wooden-handicrafts'],
                    ['name' => ['en' => 'Marble & Brass Idols', 'hi' => 'मार्बल और ब्रास मूर्तियां'], 'slug' => 'marble-brass-idols'],
                    ['name' => ['en' => 'Festival Gift Hampers', 'hi' => 'त्यौहार गिफ्ट हैम्पर्स'], 'slug' => 'festival-gift-hampers'],
                    ['name' => ['en' => 'Customized Photo Gifts', 'hi' => 'कस्टमाइज्ड गिफ्ट्स'], 'slug' => 'customized-photo-gifts'],
                    ['name' => ['en' => 'Traditional Terracotta Pottery', 'hi' => 'मिट्टी के बर्तन और सजावट'], 'slug' => 'traditional-terracotta-pottery'],
                    ['name' => ['en' => 'Corporate Executive Gifts', 'hi' => 'कॉरपोरेट गिफ्ट्स'], 'slug' => 'corporate-executive-gifts'],
                ]
            ],
            [
                'name' => ['en' => 'Baby & Kids', 'hi' => 'बेबी और किड्स', 'mr' => 'बेबी व किड्स'],
                'slug' => 'baby-kids',
                'description' => ['en' => 'Baby clothing, diapers, toys, feeding bottles, and strollers', 'hi' => 'बच्चों के कपड़े, डायपर, खिलौने और फीडिंग बोतल'],
                'icon' => 'Baby',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Baby Clothing & Onesies', 'hi' => 'छोटे बच्चों के कपड़े'], 'slug' => 'baby-clothing-onesies'],
                    ['name' => ['en' => 'Diapers & Baby Wipes', 'hi' => 'डायपर और बेबी वाइप्स'], 'slug' => 'diapers-baby-wipes'],
                    ['name' => ['en' => 'Baby Bath & Skin Care', 'hi' => 'बेबी बाथ और स्किनकेयर'], 'slug' => 'baby-bath-skin-care'],
                    ['name' => ['en' => 'Toys & Educational Games', 'hi' => 'खिलौने और गेम्स'], 'slug' => 'toys-educational-games'],
                    ['name' => ['en' => 'Baby Feeding & Bottles', 'hi' => 'बेबी फीडिंग और बोतल'], 'slug' => 'baby-feeding-bottles'],
                    ['name' => ['en' => 'Strollers & Baby Gear', 'hi' => 'बेबी स्ट्रोलर और गियर'], 'slug' => 'strollers-baby-gear'],
                ]
            ],
            [
                'name' => ['en' => 'Oil', 'hi' => 'तेल', 'mr' => 'तेल'],
                'slug' => 'oil',
                'description' => ['en' => 'Cold pressed groundnut oil, mustard oil, til oil, and coconut oil', 'hi' => 'मूंगफली, सरसों, तिल और नारियल का शुद्ध तेल'],
                'icon' => 'Droplet',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Cold-Pressed Groundnut Oil', 'hi' => 'मूंगफली का तेल'], 'slug' => 'cold-pressed-groundnut-oil'],
                    ['name' => ['en' => 'Pure Mustard Oil (Sarson)', 'hi' => 'सरसों का तेल'], 'slug' => 'pure-mustard-oil'],
                    ['name' => ['en' => 'Sesame & Til Oil', 'hi' => 'तिल का तेल'], 'slug' => 'sesame-til-oil'],
                    ['name' => ['en' => 'Virgin Coconut Oil', 'hi' => 'नारियल का तेल'], 'slug' => 'virgin-coconut-oil'],
                    ['name' => ['en' => 'Sunflower & Rice Bran Oil', 'hi' => 'सनफ्लावर ऑयल'], 'slug' => 'sunflower-rice-bran-oil'],
                    ['name' => ['en' => 'Ayurvedic Massage Oils', 'hi' => 'आयुर्वेदिक मालिश का तेल'], 'slug' => 'ayurvedic-massage-oils'],
                ]
            ],
            [
                'name' => ['en' => 'Papad & Kurdai', 'hi' => 'पापड़ और कुरडई', 'mr' => 'पापड आणि कुरडई'],
                'slug' => 'papad-kurdai',
                'description' => ['en' => 'Udad dal papad, moong dal papad, wheat kurdai, and sabudana wafers', 'hi' => 'उड़द दाल पापड़, मूंग दाल पापड़ और गेहूं की कुरडई'],
                'icon' => 'Cookie',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Udad Dal Papad', 'hi' => 'उड़द दाल पापड़'], 'slug' => 'udad-dal-papad'],
                    ['name' => ['en' => 'Moong Dal Papad', 'hi' => 'मूंग दाल पापड़'], 'slug' => 'moong-dal-papad'],
                    ['name' => ['en' => 'Traditional Wheat Kurdai', 'hi' => 'गेहूं की कुरडई'], 'slug' => 'traditional-wheat-kurdai'],
                    ['name' => ['en' => 'Rice Papad & Chawal Wafers', 'hi' => 'चावल के पापड़'], 'slug' => 'rice-papad-chawal-wafers'],
                    ['name' => ['en' => 'Sabudana & Potato Wafers', 'hi' => 'साबूदाना और आलू वेफर्स'], 'slug' => 'sabudana-potato-wafers'],
                    ['name' => ['en' => 'Spicy & Masala Papad', 'hi' => 'मसाला पापड़'], 'slug' => 'spicy-masala-papad'],
                ]
            ],
            [
                'name' => ['en' => 'Astro Stone', 'hi' => 'एस्ट्रो स्टोन और रत्न', 'mr' => 'ॲस्ट्रो स्टोन व रत्ने'],
                'slug' => 'astro-stone',
                'description' => ['en' => 'Certified gemstones, yellow sapphire, emerald, ruby, and rudraksha rings', 'hi' => 'प्रमाणित पुखराज, नीलम, पन्ना, माणिक और मूंगा रत्न'],
                'icon' => 'Sparkles',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Yellow Sapphire (Pukhraj)', 'hi' => 'पीला पुखराज'], 'slug' => 'yellow-sapphire-pukhraj'],
                    ['name' => ['en' => 'Blue Sapphire (Neelam)', 'hi' => 'नीलम रत्न'], 'slug' => 'blue-sapphire-neelam'],
                    ['name' => ['en' => 'Emerald (Panna)', 'hi' => 'पन्ना रत्न'], 'slug' => 'emerald-panna'],
                    ['name' => ['en' => 'Ruby (Manik)', 'hi' => 'माणिक रत्न'], 'slug' => 'ruby-manik'],
                    ['name' => ['en' => 'Red Coral (Moonga)', 'hi' => 'मूंगा रत्न'], 'slug' => 'red-coral-moonga'],
                    ['name' => ['en' => 'Pearl (Moti) & Gemstone Rings', 'hi' => 'मोती और रत्नों की अंगूठी'], 'slug' => 'pearl-moti-rings'],
                ]
            ],
            [
                'name' => ['en' => 'Diwali Faral', 'hi' => 'दिवाली फराल', 'mr' => 'दिवाळी फराळ'],
                'slug' => 'diwali-faral',
                'description' => ['en' => 'Crispy chakli, poha chivda, karanji, besan ladoo, and faral hampers', 'hi' => 'चकली, पोहा चिवड़ा, करंजी, बेसन लड्डू और फराल हैम्पर्स'],
                'icon' => 'Gift',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Crunchy Chakli & Chivda', 'hi' => 'चकली और पोहा चिवड़ा'], 'slug' => 'crunchy-chakli-chivda'],
                    ['name' => ['en' => 'Sweet Karanji & Anarse', 'hi' => 'करंजी और अनारसे'], 'slug' => 'sweet-karanji-anarse'],
                    ['name' => ['en' => 'Besan & Rava Ladoo', 'hi' => 'बेसन और रवा लड्डू'], 'slug' => 'besan-rava-ladoo'],
                    ['name' => ['en' => 'Shankarpali & Kadboli', 'hi' => 'शंकरपाली और कड़बौली'], 'slug' => 'shankarpali-kadboli'],
                    ['name' => ['en' => 'Dry Fruit Faral Mix', 'hi' => 'ड्राई फ्रूट फराल मिक्स'], 'slug' => 'dry-fruit-faral-mix'],
                    ['name' => ['en' => 'Traditional Faral Hampers', 'hi' => 'दिवाली फराल गिफ्ट हैम्पर'], 'slug' => 'traditional-faral-hampers'],
                ]
            ],
            [
                'name' => ['en' => 'Electronics', 'hi' => 'इलेक्ट्रॉनिक्स', 'mr' => 'इलेक्ट्रॉनिक्स'],
                'slug' => 'electronics',
                'description' => ['en' => 'Smartphones, laptops, smart watches, bluetooth speakers, and power banks', 'hi' => 'स्मार्टफोन, लैपटॉप, स्मार्टवॉच और ऑडियो एक्सेसरीज'],
                'icon' => 'Laptop',
                'is_featured' => true,
                'subcategories' => [
                    ['name' => ['en' => 'Smartphones & Mobiles', 'hi' => 'स्मार्टफोन और मोबाइल'], 'slug' => 'smartphones-mobiles'],
                    ['name' => ['en' => 'Laptops & Computers', 'hi' => 'लैपटॉप और कंप्यूटर'], 'slug' => 'laptops-computers'],
                    ['name' => ['en' => 'Smart Watches & Fitness Bands', 'hi' => 'स्मार्टवॉच और फिटनेस बैंड'], 'slug' => 'smart-watches-fitness-bands'],
                    ['name' => ['en' => 'Bluetooth Speakers & Audio', 'hi' => 'स्पीकर और हेडफोन'], 'slug' => 'bluetooth-speakers-audio'],
                    ['name' => ['en' => 'Power Banks & Cables', 'hi' => 'पावर बैंक और केबल'], 'slug' => 'power-banks-cables'],
                    ['name' => ['en' => 'Home Electronic Appliances', 'hi' => 'होम इलेक्ट्रॉनिक उपकरण'], 'slug' => 'home-electronic-appliances'],
                ]
            ],
        ];

        foreach ($categoriesData as $catIndex => $cData) {
            $parent = Category::updateOrCreate(
                ['slug' => $cData['slug']],
                [
                    'parent_id' => null,
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

        Cache::flush();
    }
}

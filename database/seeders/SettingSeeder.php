<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'JSS Solutions Multi Vendor Marketplace',
                'group' => 'general',
            ],
            [
                'key' => 'site_currency',
                'value' => 'INR',
                'group' => 'general',
            ],
            [
                'key' => 'free_shipping_threshold',
                'value' => 499,
                'group' => 'shipping',
            ],
            [
                'key' => 'default_shipping_fee',
                'value' => 40,
                'group' => 'shipping',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => false,
                'group' => 'maintenance',
            ],
            [
                'key' => 'social_links',
                'value' => [
                    'facebook' => 'https://facebook.com/jsssolutions',
                    'instagram' => 'https://instagram.com/jsssolutions',
                    'twitter' => 'https://twitter.com/jsssolutions',
                ],
                'group' => 'social',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::set($setting['key'], $setting['value'], $setting['group']);
        }
    }
}

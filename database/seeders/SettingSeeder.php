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
            // Public Settings
            [
                'key' => 'site_name',
                'value' => 'JSS Solutions Multi Vendor Marketplace',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'site_currency',
                'value' => 'INR',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'free_shipping_threshold',
                'value' => 499,
                'group' => 'shipping',
                'is_public' => true,
            ],
            [
                'key' => 'default_shipping_fee',
                'value' => 40,
                'group' => 'shipping',
                'is_public' => true,
            ],
            [
                'key' => 'maintenance_mode',
                'value' => false,
                'group' => 'maintenance',
                'is_public' => true,
            ],
            [
                'key' => 'social_links',
                'value' => [
                    'facebook' => 'https://facebook.com/jsssolutions',
                    'instagram' => 'https://instagram.com/jsssolutions',
                    'twitter' => 'https://twitter.com/jsssolutions',
                ],
                'group' => 'social',
                'is_public' => true,
            ],

            // Private Settings (Admin Only)
            [
                'key' => 'smtp_config',
                'value' => [
                    'host' => 'smtp.mailtrap.io',
                    'port' => 2525,
                    'username' => 'encrypted_smtp_user',
                    'password' => 'encrypted_smtp_pass',
                ],
                'group' => 'smtp',
                'is_public' => false,
            ],
            [
                'key' => 'razorpay_credentials',
                'value' => [
                    'key_id' => 'rzp_test_sample_key',
                    'key_secret' => 'sample_secret_key_hidden',
                ],
                'group' => 'payment',
                'is_public' => false,
            ],
            [
                'key' => 'tax_rate',
                'value' => 18.00, // 18% GST
                'group' => 'tax',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::set($setting['key'], $setting['value'], $setting['group'], $setting['is_public']);
        }
    }
}

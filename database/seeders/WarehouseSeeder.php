<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Mumbai Central Fulfillment Hub',
                'code' => 'WH-MUM-01',
                'contact_name' => 'Rajesh Sharma',
                'contact_email' => 'mumbai.wh@jss.solutions',
                'contact_phone' => '+912298765432',
                'address_line_1' => 'Plot 42, MIDC Industrial Area',
                'address_line_2' => 'Andheri East',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400093',
                'is_primary' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Delhi NCR Logistics Hub',
                'code' => 'WH-DEL-01',
                'contact_name' => 'Amit Kumar',
                'contact_email' => 'delhi.wh@jss.solutions',
                'contact_phone' => '+911198765433',
                'address_line_1' => 'Sector 37, Pace City II',
                'address_line_2' => 'Gurugram',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'pincode' => '122001',
                'is_primary' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Bangalore Tech Park Warehouse',
                'code' => 'WH-BLR-01',
                'contact_name' => 'Priya Nair',
                'contact_email' => 'bangalore.wh@jss.solutions',
                'contact_phone' => '+918098765434',
                'address_line_1' => 'Whitefield Industrial Zone',
                'address_line_2' => 'Hoodi',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'pincode' => '560048',
                'is_primary' => false,
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $wData) {
            Warehouse::updateOrCreate(
                ['code' => $wData['code']],
                $wData
            );
        }
    }
}

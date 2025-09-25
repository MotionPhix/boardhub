<?php

namespace Database\Seeders;

use App\Models\Billboard;
use Illuminate\Database\Seeder;

class BillboardSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $billboards = [
            [
                'name' => 'City Center Premium',
                'location' => 'Lilongwe City Centre, Central Region',
                'size' => '6x4 meters',
                'price' => 85000.00,
                'status' => 'available',
                'description' => 'Prime location in Lilongwe city center with maximum visibility for shoppers and commuters.',
            ],
            [
                'name' => 'M1 Highway Junction',
                'location' => 'Lilongwe-Blantyre Highway, Dedza',
                'size' => '8x6 meters',
                'price' => 120000.00,
                'status' => 'available',
                'description' => 'Strategic location at major highway junction between Malawi\'s two largest cities.',
            ],
            [
                'name' => 'Blantyre Commercial District',
                'location' => 'Victoria Avenue, Blantyre',
                'size' => '5x4 meters',
                'price' => 75000.00,
                'status' => 'available',
                'description' => 'High-traffic commercial area in Blantyre\'s business district.',
            ],
            [
                'name' => 'Mzuzu University Campus',
                'location' => 'Mzuzu University Road, Northern Region',
                'size' => '4x3 meters',
                'price' => 45000.00,
                'status' => 'available',
                'description' => 'Perfect for targeting students and university community in Northern Malawi.',
            ],
            [
                'name' => 'Kamuzu Central Hospital',
                'location' => 'Kamuzu Central Hospital Road, Lilongwe',
                'size' => '6x4 meters',
                'price' => 65000.00,
                'status' => 'occupied',
                'description' => 'High-visibility location near Malawi\'s largest hospital.',
            ],
            [
                'name' => 'Zomba Market Square',
                'location' => 'Market Square, Zomba',
                'size' => '4x4 meters',
                'price' => 55000.00,
                'status' => 'available',
                'description' => 'Central market location in the former capital city with heavy foot traffic.',
            ],
            [
                'name' => 'Crossroads Mall Entrance',
                'location' => 'Crossroads Shopping Centre, Lilongwe',
                'size' => '8x4 meters',
                'price' => 95000.00,
                'status' => 'available',
                'description' => 'Premium mall entrance location targeting middle-class shoppers.',
            ],
            [
                'name' => 'Bingu Stadium Approach',
                'location' => 'Bingu National Stadium, Lilongwe',
                'size' => '10x6 meters',
                'price' => 110000.00,
                'status' => 'maintenance',
                'description' => 'Large format billboard near national stadium, perfect for major campaigns.',
            ],
        ];

        foreach ($billboards as $billboard) {
            Billboard::create($billboard);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\RfidCard;
use Illuminate\Database\Seeder;

class RfidCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cards = [
            [
                'uid' => '82 3E 19 B0',
                'name' => 'AHONG!',
                'role' => 'admin',
                'active' => true,
            ],
            [
                'uid' => '07 86 33 4B',
                'name' => 'RAFII',
                'role' => 'owner',
                'active' => true,
            ],
            [
                'uid' => 'D0 7D 16 54',
                'name' => 'ARDAFA',
                'role' => 'teknisi',
                'active' => true,
            ],
        ];

        foreach ($cards as $card) {
            RfidCard::firstOrCreate(
                ['uid' => $card['uid']],
                $card
            );
        }
    }
}


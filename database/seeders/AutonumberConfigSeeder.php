<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AutonumberConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Kategori: KTG001, KTG002, ... (6 karakter, muat di kolom kode & form frontend)
        DB::table('__autonumber_config')->updateOrInsert(
            ['form_id' => 'kategori'],
            [
                'format_auto'  => 'KTG{number}',
                'number_digit' => 3,
                'reset_by'     => null,
                'create_by'    => 'seeder',
                'create_time'  => now(),
            ]
        );
    }
}
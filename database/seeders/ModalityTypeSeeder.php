<?php

namespace Database\Seeders;

use App\Models\ModalityType;
use Illuminate\Database\Seeder;

class ModalityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ModalityType::insert([
            ['name' => 'Sistema Certifica'],
            ['name' => 'Usuario Externo'],
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\SectionType;
use Illuminate\Database\Seeder;

class SectionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SectionType::insert([
            ['name' => 'Módulos'],
            ['name' => 'Protocolos'],
        ]);
    }
}

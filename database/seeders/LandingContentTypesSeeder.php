<?php

namespace Database\Seeders;

use App\Models\LandingContentType;
use Illuminate\Database\Seeder;

class LandingContentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LandingContentType::insert([
            ['name' => 'Carrusel'],
            ['name' => 'Preguntas Frecuentes'],
            ['name' => 'Requisitos obligatorios - Capacitación por Sistema Certifica'],
            ['name' => 'Requisitos obligatorios: Capacitación por Usuario Externo'],
            ['name' => 'Instituciones'],
            ['name' => 'Recursos'],
            ['name' => 'Ayuda'],
        ]);
    }
}

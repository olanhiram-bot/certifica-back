<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        Section::insert([
            ['name' => 'Módulo 1: Introducción a la plataforma', 'section_type_id' => 1],
            ['name' => 'Módulo 2: Comunicación y colaboración', 'section_type_id' => 1],
            ['name' => 'Módulo 3: Gestión de procesos', 'section_type_id' => 1],
            ['name' => 'Módulo 4: Calidad y mejora continua', 'section_type_id' => 1],
            ['name' => '1. Política interna de operación', 'section_type_id' => 2],
            ['name' => '2. Procedimiento de atención y seguimiento', 'section_type_id' => 2],
            ['name' => '3. Capacitación y participación', 'section_type_id' => 2],
            ['name' => '4. Comunicación interna', 'section_type_id' => 2],
            ['name' => '5. Mejora continua y resultados', 'section_type_id' => 2],
        ]);
    }
}

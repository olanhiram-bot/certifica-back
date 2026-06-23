<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Section::insert([
            ['name' => 'Módulo 1: Perspectiva de género', 'section_type_id' => 1],
            ['name' => 'Módulo 2: Prevención de la violencia de género', 'section_type_id' => 1],
            ['name' => 'Módulo 3: Atención a casos', 'section_type_id' => 1],
            ['name' => 'Módulo 4: Trabajo con masculinidades', 'section_type_id' => 1],
            ['name' => '1-. Pólitica de igualdad laboral y no discriminación', 'section_type_id' => 2],
            ['name' => '2.- Protocolo de atención a casos de violencia y discriminación por razón de género en el ámbito laboral', 'section_type_id' => 2],
            ['name' => '3.- Capacidad en perspectiva de género', 'section_type_id' => 2],
            ['name' => '4.- Campañas de difusión interna', 'section_type_id' => 2],
            ['name' => '5.- Acción de responsabilidad', 'section_type_id' => 2],
        ]);
    }
}

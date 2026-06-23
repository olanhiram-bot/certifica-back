<?php

namespace Database\Seeders;

use App\Models\StampActivityStatus;
use Illuminate\Database\Seeder;

class StampActivityStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StampActivityStatus::insert([
            ['name' => 'Sin Iniciar', 'hexadecimal' => '#9164CC'],
            ['name' => 'Pendiente', 'hexadecimal' => '#FFA400'],
            ['name' => 'Con Evidencia', 'hexadecimal' => '#df78df'],
            ['name' => 'En Revisión', 'hexadecimal' => '#0075C9'],
            ['name' => 'Con Observaciones', 'hexadecimal' => '#FF4438'],
            ['name' => 'Aprobado', 'hexadecimal' => '#00BC70'],
        ]);
    }
}

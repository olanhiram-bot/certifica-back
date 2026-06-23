<?php

namespace Database\Seeders;

use App\Models\StampStatus;
use Illuminate\Database\Seeder;

class StampStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StampStatus::insert([
            ['name' => 'Nuevo', 'hexadecimal' => '#9164CC'],
            ['name' => 'Por Revisar', 'hexadecimal' => '#FFA400'],
            ['name' => 'En Revisión', 'hexadecimal' => '#0075C9'],
            ['name' => 'Aprobado', 'hexadecimal' => '#00BC70'],
            ['name' => 'Vencido', 'hexadecimal' => '#eb551a'],
            ['name' => 'Rechazado', 'hexadecimal' => '#FF4438'],
        ]);
    }
}

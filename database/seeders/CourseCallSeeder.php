<?php

namespace Database\Seeders;

use App\Models\CourseCall;
use Illuminate\Database\Seeder;

class CourseCallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CourseCall::insert([
            [
                'name' => 'Convocatoria 1',
                'vigency_start_date' => '2026-03-01',
                'vigency_end_date' => '2026-07-31',
            ],
        ]);
    }
}

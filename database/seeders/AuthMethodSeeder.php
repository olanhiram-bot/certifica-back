<?php

namespace Database\Seeders;

use App\Models\AuthMethod;
use Illuminate\Database\Seeder;

class AuthMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AuthMethod::insert([
            ['name' => 'django'],
            ['name' => 'sso'],
        ]);
    }
}

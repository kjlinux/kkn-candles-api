<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'email' => 'admin@kkncandles.com',
            'password' => 'admin123',
            'first_name' => 'Admin',
            'last_name' => 'KKN',
            'phone' => '+226 70 00 00 00',
            'role' => 'admin',
            'city' => 'Ouagadougou',
            'email_verified_at' => now(),
        ]);
    }
}

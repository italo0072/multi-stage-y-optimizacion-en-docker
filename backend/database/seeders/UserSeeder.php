<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'cliente@demo.com'],
            [
                'name' => 'Cliente Demo',
                'phone' => '+503 2121 2828',
                'password' => Hash::make('password123'),
            ]
        );

        User::factory()->count(5)->create();
    }
}

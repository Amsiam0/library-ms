<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
            'role'  => 'admin',
        ]);
        User::factory()->create([
            'name'  => 'Normal User',
            'email' => 'user@example.com',
            'role'  => 'user',
        ]);

        // Regular users
        User::factory(10)->create();
    }
}

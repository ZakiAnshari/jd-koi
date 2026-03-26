<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Hanya 1 user admin (default)
        $this->call(UserSeeder::class);
    }
}

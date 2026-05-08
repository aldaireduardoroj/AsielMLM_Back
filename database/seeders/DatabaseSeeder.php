<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Vithara Admin',
            'email' => 'josepmachuca8@gmail.com',
            'password' => bcrypt("Diosesmiescudo"),
            'uuid'     => "48466030",
            'is_admin' => true
        ]);
    }
}

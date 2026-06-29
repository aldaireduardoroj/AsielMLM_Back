<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Option;

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
            'email' => 'admin@gmail.com',
            'password' => bcrypt("123456"),
            'uuid'     => "99999999",
            'is_admin' => true
        ]);

        Option::create(['option_key' => 'comision']);
        Option::create(['logo' => 'comision']);
    }
}

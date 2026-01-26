<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Abraham Alanya',
            'email' => 'abrahamalanya@laravel.com',
            'password' => bcrypt('abrahamalanya'),
        ]);
        $user->assignRole('sistema');
    }
}

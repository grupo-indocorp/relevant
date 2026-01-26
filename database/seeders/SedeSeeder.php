<?php

namespace Database\Seeders;

use App\Models\Sede;
use Illuminate\Database\Seeder;

class SedeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sede::create([
            'nombre' => 'Huancayo',
        ]);

        Sede::create([
            'nombre' => 'Lima',
        ]);
    }
}

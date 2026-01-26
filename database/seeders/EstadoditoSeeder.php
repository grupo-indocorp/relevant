<?php

namespace Database\Seeders;

use App\Models\Estadodito;
use Illuminate\Database\Seeder;

class EstadoditoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Estadodito::create([
            'nombre' => 'FINANCIADO',
        ]);

        Estadodito::create([
            'nombre' => 'UPFRONT',
        ]);

        Estadodito::create([
            'nombre' => 'BLOQUEADO',
        ]);
    }
}

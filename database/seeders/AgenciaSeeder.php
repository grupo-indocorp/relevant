<?php

namespace Database\Seeders;

use App\Models\Agencia;
use Illuminate\Database\Seeder;

class AgenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Agencia::create([
            'nombre' => 'INDOTECH',
        ]);

        Agencia::create([
            'nombre' => 'VACANTE',
        ]);

        Agencia::create([
            'nombre' => 'OTROS',
        ]);
    }
}

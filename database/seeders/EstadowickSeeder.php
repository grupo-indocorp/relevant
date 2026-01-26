<?php

namespace Database\Seeders;

use App\Models\Estadowick;
use Illuminate\Database\Seeder;

class EstadowickSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Estadowick::create([
            'nombre' => 'APROBADO',
        ]);

        Estadowick::create([
            'nombre' => 'OBSERVADO',
        ]);

        Estadowick::create([
            'nombre' => 'RECHAZADO',
        ]);
    }
}

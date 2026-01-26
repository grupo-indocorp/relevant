<?php

namespace Database\Seeders;

use App\Models\Etapa;
use Illuminate\Database\Seeder;

class EtapaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Etapa::create([
            'nombre' => 'Oportunidades',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 10,
        ]);
        Etapa::create([
            'nombre' => 'Prospectos',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 20,
        ]);
        Etapa::create([
            'nombre' => 'Pre Clientes',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 30,
        ]);
        Etapa::create([
            'nombre' => 'Ventas',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 40,
        ]);
        Etapa::create([
            'nombre' => 'No Vendidos',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 50,
        ]);
        Etapa::create([
            'nombre' => 'Clientes Reportados',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 60,
        ]);
    }
}

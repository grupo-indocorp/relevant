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
        Etapa::factory()->create([
            'nombre' => 'Oportunidades',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 10,
        ]);
        Etapa::factory()->create([
            'nombre' => 'Prospectos',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 20,
        ]);
        Etapa::factory()->create([
            'nombre' => 'Pre Clientes',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 30,
        ]);
        Etapa::factory()->create([
            'nombre' => 'Ventas',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 40,
        ]);
        Etapa::factory()->create([
            'nombre' => 'No Vendidos',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 50,
        ]);
        Etapa::factory()->create([
            'nombre' => 'Clientes Reportados',
            'color' => '#f97516',
            'opacity' => '#f975164d',
            'orden' => 60,
        ]);
    }
}

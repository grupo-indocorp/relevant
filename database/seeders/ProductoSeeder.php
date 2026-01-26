<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Producto::create([
            'nombre' => 'SOHO',
            'categoria_id' => 1,
        ]);
        Producto::create([
            'nombre' => 'Internet Empresas',
            'categoria_id' => 1,
        ]);
        Producto::create([
            'nombre' => 'BUNDLE',
            'categoria_id' => 1,
        ]);
        Producto::create([
            'nombre' => 'GPON',
            'categoria_id' => 1,
        ]);
        Producto::create([
            'nombre' => 'Internet Dedicado',
            'categoria_id' => 1,
        ]);
    }
}

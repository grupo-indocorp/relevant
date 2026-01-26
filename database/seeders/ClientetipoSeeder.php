<?php

namespace Database\Seeders;

use App\Models\Clientetipo;
use Illuminate\Database\Seeder;

class ClientetipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Clientetipo::create([
            'nombre' => 'WIN Empresas - Activo',
        ]);
        Clientetipo::create([
            'nombre' => 'WIN Empresas - Potencial',
        ]);
        Clientetipo::create([
            'nombre' => 'WIN Negocios',
        ]);
        Clientetipo::create([
            'nombre' => 'Libre',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Notificaciontipo;
use Illuminate\Database\Seeder;

class NotificaciontipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Notificaciontipo::create([
            'nombre' => 'General',
        ]);

        Notificaciontipo::create([
            'nombre' => 'Cita',
        ]);

        Notificaciontipo::create([
            'nombre' => 'Llamada',
        ]);
    }
}

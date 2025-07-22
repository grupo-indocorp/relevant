<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UbigeoSeeder::class);
        $this->call(TipodocumentoSeeder::class);
        $this->call(EstadouserSeeder::class);
        $this->call(GenerouserSeeder::class);
        $this->call(ModalidaduserSeeder::class);
        $this->call(ContratotipoSeeder::class);
        $this->call(PlanillaempresaSeeder::class);
        $this->call(CuentafinancieracicloSeeder::class);
        $this->call(EstadofacturaSeeder::class);
        $this->call(EstadoproductoSeeder::class);
        $this->call(SedeSeeder::class);
        $this->call(EstadowickSeeder::class);
        $this->call(EstadoditoSeeder::class);
        $this->call(ClientetipoSeeder::class);
        $this->call(AgenciaSeeder::class);
        $this->call(NotificaciontipoSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(EtiquetaSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(EtapaSeeder::class);
        $this->call(CategoriaSeeder::class);
        $this->call(ProductoSeeder::class);
    }
}

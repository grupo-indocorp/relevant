<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbigeoSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('distritos')->truncate();
        DB::table('provincias')->truncate();
        DB::table('departamentos')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $file = public_path('ubigeos.csv');
        $handle = fopen($file, 'r');

        $departamentos = [];
        $provincias = [];
        $distritos = [];

        // Saltar la primera fila si tiene titulos
        fgetcsv($handle);

        while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
            [$depNombre, $provNombre, $distNombre, $ubigeo] = $row;

            $depCodigo = substr($ubigeo, 0, 2);
            $provCodigo = substr($ubigeo, 0, 4);
            $distCodigo = $ubigeo;

            // Departamento
            $departamentos[$depCodigo] = [
                'codigo' => $depCodigo,
                'nombre' => strtoupper($depNombre),
            ];

            // Provincia
            $provincias[$provCodigo] = [
                'codigo' => $provCodigo,
                'nombre' => strtoupper($provNombre),
                'departamento_codigo' => $depCodigo,
            ];

            // Distrito
            $distritos[$distCodigo] = [
                'codigo' => $distCodigo,
                'nombre' => strtoupper($distNombre),
                'provincia_codigo' => $provCodigo,
                'departamento_codigo' => $depCodigo,
            ];
        }

        fclose($handle);

        // Insertar datos limpios en DB
        DB::table('departamentos')->insert(array_values($departamentos));
        DB::table('provincias')->insert(array_values($provincias));
        DB::table('distritos')->insert(array_values($distritos));
    }
}

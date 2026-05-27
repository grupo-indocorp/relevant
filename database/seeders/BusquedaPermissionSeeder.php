<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BusquedaPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos para el sistema de búsqueda
        $permissions = [
            // Permisos generales del módulo
            'busqueda.access',
            'busqueda.view',
            
            // Permisos de vista específicos
            'busqueda.view.ruc20.individual',
            'busqueda.view.ruc20.masivo',
            'busqueda.view.ruc20.export',
            'busqueda.view.ruc20.stats',
            'busqueda.view.ruc10.individual',
            'busqueda.view.ruc10.masivo',
            'busqueda.view.ruc10.export',
            'busqueda.view.ruc10.stats',
            
            // Permisos para búsqueda RUC 20
            'busqueda.ruc20.individual',
            'busqueda.ruc20.masivo',
            'busqueda.ruc20.export',
            'busqueda.ruc20.stats',
            
            // Permisos para búsqueda DNI/RUC 10
            'busqueda.ruc10.individual',
            'busqueda.ruc10.masivo',
            'busqueda.ruc10.export',
            'busqueda.ruc10.stats',
            
            // Permisos administrativos
            'busqueda.admin',
        ];

        // Crear permisos
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Obtener roles existentes o crearlos si no existen
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $consultorRole = Role::firstOrCreate(['name' => 'consultor', 'guard_name' => 'web']);
        $sistemaRole = Role::firstOrCreate(['name' => 'sistema', 'guard_name' => 'web']);

        // Asignar todos los permisos al rol admin
        $adminRole->givePermissionTo($permissions);

        // Asignar permisos básicos al rol user
        $userRole->givePermissionTo([
            'busqueda.access',
            'busqueda.view',
            'busqueda.view.ruc20.individual',
            'busqueda.view.ruc10.individual',
            'busqueda.ruc20.individual',
            'busqueda.ruc10.individual',
        ]);

        // Asignar permisos extendidos al rol consultor
        $consultorRole->givePermissionTo([
            'busqueda.access',
            'busqueda.view',
            'busqueda.view.ruc20.individual',
            'busqueda.view.ruc20.masivo',
            'busqueda.view.ruc20.export',
            'busqueda.view.ruc10.individual',
            'busqueda.view.ruc10.masivo',
            'busqueda.view.ruc10.export',
            'busqueda.ruc20.individual',
            'busqueda.ruc20.masivo',
            'busqueda.ruc20.export',
            'busqueda.ruc10.individual',
            'busqueda.ruc10.masivo',
            'busqueda.ruc10.export',
        ]);

        // Asignar todos los permisos al rol sistema
        $sistemaRole->givePermissionTo($permissions);

        $this->command->info('Permisos del sistema de búsqueda creados exitosamente.');
    }
}

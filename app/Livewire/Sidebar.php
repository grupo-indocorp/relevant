<?php

namespace App\Livewire;

use Livewire\Component;

class Sidebar extends Component
{
    public function render()
    {
        $links = [
            [
                'icon' => 'fa-solid fa-chart-line',
                'nombre' => 'Dashboard',
                'url' => 'dashboard',
                'can' => 'sistema.dashboard',
            ],
            [
                'icon' => 'fa-regular fa-magnifying-glass',
                'nombre' => 'Consultor Clientes',
                'url' => 'cliente-consultor',
                'can' => 'sistema.cliente',
            ],
            [
                'icon' => 'fa-solid fa-building-columns',
                'nombre' => 'Consultor SUNAT',
                'url' => 'busqueda',
                'can' => 'busqueda.access',
            ],
            [
                'icon' => 'fa-table',
                'nombre' => 'Funnel',
                'url' => 'funnel',
                'can' => 'sistema.funnel',
            ],
            [
                'icon' => 'fa-solid fa-table-list',
                'nombre' => 'Gestión Clientes',
                'url' => 'cliente-gestion',
                'can' => 'sistema.gestion_cliente',
            ],
            // [
            //     'icon' => 'fa-regular fa-calendar-days',
            //     'nombre' => 'Agenda',
            //     'url' => 'notificacion',
            //     'can' => 'sistema.notificacion',
            // ],
            [
                'icon' => 'fa-users',
                'nombre' => 'Lista de Usuarios',
                'url' => 'lista_usuario',
                'can' => 'sistema.lista_usuario',
            ],
            [
                'icon' => 'fa-users-between-lines',
                'nombre' => 'Equipos',
                'url' => 'equipo',
                'can' => 'sistema.equipo',
            ],
            [
                'icon' => 'fa-chart-user',
                'nombre' => 'Reportes',
                'url' => 'reporte',
                'can' => 'sistema.reporte',
            ],
            [
                'icon' => 'fa-mug-hot',
                'nombre' => 'Evaporacion',
                'url' => 'cuentas-financieras',
                'can' => 'sistema.evaporacion',
            ],
            [
                'icon' => 'fa-calendar',
                'nombre' => 'Gestión de Evaporación',
                'url' => 'evaporacion-gestion',
                'can' => 'sistema.evaporacion-gestion',
            ],
            [
                'icon' => 'fa-tower-control',
                'nombre' => 'Roles',
                'url' => 'role',
                'can' => 'sistema.role',
            ],
            [
                'icon' => 'fa-gear',
                'nombre' => 'Configuración',
                'url' => 'configuracion',
                'can' => 'sistema.configuracion',
            ],
            // Agregar la opción "Gestión de Biblioteca"
            [
                'icon' => 'fa-file', // Ícono de FontAwesome para archivos
                'nombre' => 'Gestión de Biblioteca',
                'url' => 'files', // Ruta definida en web.php
                'can' => 'sistema.files', // Permiso necesario (ajusta según tu sistema de permisos)
            ],
            [
                'icon' => 'fa-file', // Ícono de FontAwesome para archivos
                'nombre' => 'Biblioteca',
                'url' => 'documentos', // Ruta definida en web.php
                'can' => 'sistema.vista', // Permiso necesario (ajusta según tu sistema de permisos)
            ],
        ];

        return view('livewire.sidebar', compact('links'));
    }
}

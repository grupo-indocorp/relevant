<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class BusquedaMenuProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Compartir los enlaces del sistema de búsqueda con todas las vistas
        View::composer(['layouts.app', 'layouts.user_type.auth'], function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                
                // Definir enlaces del sistema de búsqueda según permisos
                $busquedaLinks = [];
                
                if ($user->can('busqueda.access')) {
                    $busquedaLinks[] = [
                        'nombre' => 'Búsqueda RUC/DNI',
                        'url' => route('busqueda.index'),
                        'icon' => 'fa-solid fa-magnifying-glass',
                        'can' => 'busqueda.access'
                    ];
                }
                
                // Compartir las variables con las vistas
                $view->with('busquedaLinks', $busquedaLinks);
            }
        });
    }
}

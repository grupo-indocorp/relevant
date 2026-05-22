<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBusquedaPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Si no se especifica permiso, solo verificar acceso básico
        if (!$permission) {
            if (!Auth::user()->can('busqueda.access')) {
                abort(403, 'No tienes permiso para acceder al sistema de búsqueda.');
            }
        } else {
            // Verificar permiso específico
            if (!Auth::user()->can($permission)) {
                abort(403, 'No tienes permiso para realizar esta acción.');
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\IndotechFunnelExport;
use App\Exports\SecodiFunnelExport;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    public function secodiFunnel()
    {
        $filtro = request('filtro');
        $user = auth()->user();
        $nameExport = 'Secodi.xlsx';

        return (new SecodiFunnelExport($filtro, $user))->download($nameExport);
    }

    public function indotechFunnel()
    {
        $filtro = request('filtro');
        $user = auth()->user();

        // Si viene el parámetro all=1, permitir omitir las restricciones de rol sólo para administradores/sistema
        $allowAll = request()->boolean('all');
        $skipRoleRestrictions = false;
        if ($allowAll) {
            if ($user->hasRole(['administrador', 'sistema'])) {
                $skipRoleRestrictions = true;
            } else {
                return response()->json(['error' => 'No autorizado para exportar todos los datos'], 403);
            }
        }

        $export = new IndotechFunnelExport($filtro, $user, 1000, 'IndotechFunnelExport.csv', true, $skipRoleRestrictions);

        // Modo debug: devuelve count y muestra de filas para inspección rápida
        if (request()->boolean('debug')) {
            try {
                $count = $export->query()->count();
                $sample = $export->query()->limit(5)->get();
                return response()->json(['count' => $count, 'sample' => $sample, 'skipRoleRestrictions' => $skipRoleRestrictions]);
            } catch (\Throwable $e) {
                Log::error('Error en debug indotechFunnel: ' . $e->getMessage());
                return response()->json(['error' => 'Error al ejecutar la query', 'message' => $e->getMessage()], 500);
            }
        }

        Log::info('Indotech export requested', ['user_id' => $user->id ?? null, 'filtro' => $filtro, 'skipRoleRestrictions' => $skipRoleRestrictions]);

        return $export->exportToCsv();
    }
}

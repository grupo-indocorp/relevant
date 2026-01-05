<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;

class ClienteController extends Controller
{
    public function byRuc($ruc)
    {
        $cliente = Cliente::where('ruc', $ruc)->first();

        if (!$cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        return response()->json([
            'id' => $cliente->id,
            'ruc' => $cliente->ruc,
            'razon_social' => $cliente->razon_social,
            'estado' => $cliente->estado,
            'condicion' => $cliente->condicion,
            'actividad_economica' => $cliente->actividad_economica,
            'ciudad' => $cliente->ciudad,
            'departamento_codigo' => $cliente->departamento_codigo,
            'provincia_codigo' => $cliente->provincia_codigo,
            'distrito_codigo' => $cliente->distrito_codigo,
            'fecha_gestion' => $cliente->fecha_gestion,
            'equipo' => $cliente->equipo->nombre,
            'ejecutivo' => $cliente->user->name,
            'etapa' => $cliente->etapa->nombre,
        ]);
    }
}

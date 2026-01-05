<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Comentario;
use App\Models\Etapa;
use App\Services\ClienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClienteConsultorController extends Controller
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $busqueda = request('query');
        $cliente = null;
        if (isset($busqueda)) {
            if (Str::length($busqueda) == 11) {
                $cliente = $this->clienteService->consultorCliente($busqueda);
                if (is_null($cliente)) {
                    $mensaje = [
                        'mensaje' => 'El "RUC" no existe',
                        'color' => 'danger',
                    ];
                }
            } else {
                $mensaje = [
                    'mensaje' => 'El "RUC" debe tener 11 dígitos',
                    'color' => 'danger',
                ];
            }
        }
        $mensaje = $mensaje ?? false;

        return view('sistema.cliente.consultor.index', compact('mensaje', 'cliente'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (request('view') == 'show-cliente') {
            $cliente = Cliente::find($id);

            return view('sistema.cliente.consultor.edit', compact('cliente'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (request('view') == 'update-solicitar') {
            $request->validate(
                [
                    'etapa_id' => 'required|bail',
                    'comentario' => 'required|bail',
                ],
                [
                    'etapa_id.required' => 'La "Etapa" es obligatorio.',
                    'comentario.required' => 'El "Comentario" es obligatorio.',
                ]
            );
            // cliente
            $cliente = Cliente::find($id);
            $ejecutivo = auth()->user();
            $cliente->user_id = $ejecutivo->id;
            $cliente->usersHistorial()->attach($ejecutivo->id);
            $cliente->etapas()->attach(request('etapa_id'));
            $cliente->fecha_gestion = now();
            $cliente->fecha_nuevo = now();
            $cliente->etiqueta_id = 3; // solicitado
            $cliente->sede_id = $ejecutivo->sede_id;
            $cliente->equipo_id = $ejecutivo->equipos->last()->id;
            $cliente->etapa_id = request('etapa_id');
            $cliente->save();
            // comentario
            $etapa = Etapa::find(request('etapa_id'));
            $comentario = new Comentario;
            $comentario->comentario = request('comentario');
            $comentario->detalle = 'Cambio de etapa a '.$etapa->nombre;
            $comentario->cliente_id = $cliente->id;
            $comentario->user_id = $ejecutivo->id;
            $comentario->etiqueta_id = 3; // etiqueta_id, 3=solicitado;
            $comentario->contactabilidad_id = request('contactabilidad_id');
            $comentario->save();
            $this->clienteService->exportclienteStore($cliente->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

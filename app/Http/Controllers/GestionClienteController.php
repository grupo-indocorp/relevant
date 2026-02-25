<?php

namespace App\Http\Controllers;

use App\Exports\ClientesExport;
use App\Helpers\Helpers;
use App\Http\Requests\ClienteRequest;
use App\Imports\ClientesImport;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Etapa;
use App\Models\Sede;
use App\Models\User;
use App\Services\ClienteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GestionClienteController extends Controller
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function export()
    {
        return Excel::download(new ClientesExport, 'Clientes '.date('d-m-Y').'.xlsx');
    }

    public function import()
    {
        if (request()->hasFile('file')) {
            $user_id = request('import_user_id');
            $etiqueta_id = request('import_etiqueta_id');
            $file = request()->file('file');
            Excel::import(new ClientesImport($user_id, $etiqueta_id), $file);
            return response()->json(['success' => true, 'message' => 'Archivo importado']);
        }
        return response()->json(['success' => false, 'message' => 'No se recibió archivo'], 400);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $user = auth()->user();
        // $clientes = $this->clienteService->listarClientes($user);
        // $etapas = $this->clienteService->conteoClientesPorEtapa($user);

        // return view('sistema.gestion_cliente.index', compact('clientes', 'etapas'));
        $user = auth()->user();

        // Filtros
        $filtro_ruc = request('filtro_ruc');
        $filtro_etapa_id = request('filtro_etapa_id') * 1;
        $filtro_user_id = request('filtro_user_id') * 1;
        $filtro_equipo_id = request('filtro_equipo_id') * 1;
        $filtro_sede_id = request('filtro_sede_id') * 1;
        $filtro_fecha_desde = request('filtro_fecha_desde');
        $filtro_fecha_hasta = request('filtro_fecha_hasta');
        $filtro = [
            'filtro_ruc' => $filtro_ruc,
            'filtro_etapa_id' => $filtro_etapa_id,
            'filtro_equipo_id' => $filtro_equipo_id,
            'filtro_user_id' => $filtro_user_id,
            'filtro_sede_id' => $filtro_sede_id,
            'filtro_fecha_desde' => $filtro_fecha_desde,
            'filtro_fecha_hasta' => $filtro_fecha_hasta,
        ];

        // Listando data en los selects
        $sede_id = request('filtro_sede_id');
        $equipo_id = request('filtro_equipo_id');
        $user_id = request('filtro_user_id');
        $sedes = Sede::all();
        if ($sede_id) {
            $equipos = Equipo::where('sede_id', $sede_id)->get();
            if ($equipo_id) {
                $users = Equipo::find($equipo_id)->users;
            } else {
                $users = User::role('ejecutivo')->where('sede_id', $sede_id)->get();
            }
        } else {
            if ($user->hasRole('gerente comercial') || $user->hasRole('supervisor') || $user->hasRole('jefe comercial')) {
                $equipos = Equipo::where('sede_id', $user->sede_id)->get();
                if ($user->hasRole('supervisor') || $user->hasRole('jefe comercial')) {
                    $users = Equipo::find($user->equipo->id)->users;
                } else {
                    if ($equipo_id) {
                        $users = Equipo::find($equipo_id)->users;
                    } else {
                        $users = User::role('ejecutivo')->where('sede_id', $user->sede_id)->get();
                    }
                }
            } else {
                $equipos = Equipo::all();
                if ($equipo_id) {
                    $users = Equipo::find($equipo_id)->users;
                } else {
                    $users = User::role('ejecutivo')->get();
                }
            }
        }

        $etapas = Etapa::all();

        $where = [];
        $where[] = ['ruc', 'LIKE', $filtro_ruc.'%'];
        if ($filtro_etapa_id != 0) {
            $where[] = ['etapa_id', $filtro_etapa_id];
        }
        if ($user->hasRole('ejecutivo')) {
            $where[] = ['user_id', $user->id];
        } else {
            if ($filtro_user_id != 0) {
                $where[] = ['user_id', $filtro_user_id];
            }
        }
        if ($user->hasRole('supervisor')) {
            $where[] = ['equipo_id', $user->equipo->id];
        } else {
            if ($filtro_equipo_id != 0) {
                $where[] = ['equipo_id', $filtro_equipo_id];
            }
        }
        if (isset($filtro_fecha_desde)) {
            $where[] = ['fecha_gestion', '>=', $filtro_fecha_desde.' 00:00:00'];
        }
        if (isset($filtro_fecha_hasta)) {
            $where[] = ['fecha_gestion', '<=', $filtro_fecha_hasta.' 23:59:59'];
        }
        if ($user->hasRole('gerente comercial') || $user->hasRole('supervisor') || $user->hasRole('ejecutivo') || $user->hasRole('jefe comercial')) {
            $where[] = ['sede_id', $user->sede_id];
        } else { // administrador, sistema
            if ($filtro_sede_id != 0) {
                $where[] = ['sede_id', $filtro_sede_id];
            }
        }
        $clientes = Cliente::with(['user', 'equipo', 'sede', 'etapa', 'comentarios'])
            ->where($where)
            ->orderByDesc('fecha_gestion')
            ->paginate(50);
        $data_etapas = $this->clienteService->etapasConConteo()['data_etapas'];
        $count_total = $this->clienteService->etapasConConteo()['count_total'];

        $config = Helpers::configuracionExcelJsonGet();

        $countClienteNuevo = 0;
        $countClienteGestionado = 0;
        if ($user->hasRole('ejecutivo')) {

            $cltGestionados = Cliente::where('user_id', $user->id)->whereDate('fecha_gestion', Carbon::today())->get();
            foreach ($cltGestionados as $value) {
                if (isset($value->etiqueta)) {
                    foreach (json_decode($value->etiqueta) as $item) {
                        // Conteo de Clientes Nuevos por Ejecutivo
                        if ($item->nombre === 'nuevo' || $item->nombre === 'asignado' || $item->nombre === 'solicitado') {
                            $countClienteNuevo++;
                        }
                        // Conteo de Clientes Gestionados por Ejecutivo
                        if ($item->nombre === 'gestionado') {
                            $countClienteGestionado++;
                        }
                    }
                }
            }
        }

        return view('sistema.cliente.gestion.index', compact(
            'clientes',
            'data_etapas',
            'count_total',
            'filtro',
            'sedes',
            'equipos',
            'users',
            'config',
            'countClienteNuevo',
            'countClienteGestionado'
        ));
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
    public function store(ClienteRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        //
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente)
    {
        //
    }
}

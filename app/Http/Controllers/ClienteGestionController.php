<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Models\Cliente;
use App\Models\Comentario;
use App\Models\Contacto;
use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Equipo;
use App\Models\Etapa;
use App\Models\Movistar;
use App\Models\Provincia;
use App\Models\Sede;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\ClienteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteGestionController extends Controller
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
        $user = auth()->user();

        // Filtros
        $filtro_ruc = request('filtro_ruc');
        $filtro_etapa_id = request('filtro_etapa_id') * 1;
        $filtro_user_id = request('filtro_user_id') * 1;
        $filtro_equipo_id = request('filtro_equipo_id') * 1;
        $filtro_sede_id = request('filtro_sede_id') * 1;
        $filtro_fecha_desde = request('filtro_fecha_desde');
        $filtro_fecha_hasta = request('filtro_fecha_hasta');
        $paginate = request('paginate') ?? 50;

        // Obtener el conteo de clientes por etapa (pasando los filtros)
        $data_etapas = $this->contarClientesPorEtapa(
            $user,
            $filtro_equipo_id,
            $filtro_user_id,
            $filtro_sede_id,
            $filtro_fecha_desde,
            $filtro_fecha_hasta
        );

        $count_total = array_sum(array_column($data_etapas, 'clientes_solo_count')); // Total general de clientes

        
        $filtro = [
            'filtro_ruc' => $filtro_ruc,
            'filtro_etapa_id' => $filtro_etapa_id,
            'filtro_equipo_id' => $filtro_equipo_id,
            'filtro_user_id' => $filtro_user_id,
            'filtro_sede_id' => $filtro_sede_id,
            'filtro_fecha_desde' => $filtro_fecha_desde,
            'filtro_fecha_hasta' => $filtro_fecha_hasta,
            'paginate' => $paginate,
        ];

        // Resto de la lógica para obtener sedes, equipos, usuarios, etc...
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
            if ($user->hasRole('jefe comercial') || $user->hasRole('supervisor')) {
                $equipos = Equipo::where('sede_id', $user->sede_id)->get();
                if ($user->hasRole('supervisor')) {
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

        // $etapas = Etapa::all();
        $etapas = Etapa::where('estado', true)->get();

        // Resto de la lógica para filtrar clientes...
        $where = [];
        $where[] = ['ruc', 'LIKE', $filtro_ruc . '%'];
        $orwhere[] = ['razon_social', 'LIKE', '%' . $filtro_ruc . '%'];

        if ($filtro_etapa_id != 0) {
            $where[] = ['etapa_id', $filtro_etapa_id];
            $orwhere[] = ['etapa_id', $filtro_etapa_id];
        }

        if ($user->hasRole('ejecutivo')) {
            $where[] = ['user_id', $user->id];
            $orwhere[] = ['user_id', $user->id];
        } else {
            if ($filtro_user_id != 0) {
                $where[] = ['user_id', $filtro_user_id];
                $orwhere[] = ['user_id', $filtro_user_id];
            }
        }

        if ($user->hasRole('supervisor')) {
            $where[] = ['equipo_id', $user->equipo->id];
            $orwhere[] = ['equipo_id', $user->equipo->id];
        } else {
            if ($filtro_equipo_id != 0) {
                $where[] = ['equipo_id', $filtro_equipo_id];
                $orwhere[] = ['equipo_id', $filtro_equipo_id];
            }
        }

        if (isset($filtro_fecha_desde)) {
            $where[] = ['fecha_gestion', '>=', $filtro_fecha_desde . ' 00:00:00'];
            $orwhere[] = ['fecha_gestion', '>=', $filtro_fecha_desde . ' 00:00:00'];
        }

        if (isset($filtro_fecha_hasta)) {
            $where[] = ['fecha_gestion', '<=', $filtro_fecha_hasta . ' 23:59:59'];
            $orwhere[] = ['fecha_gestion', '<=', $filtro_fecha_hasta . ' 23:59:59'];
        }

        if ($user->hasRole('jefe comercial') || $user->hasRole('supervisor') || $user->hasRole('ejecutivo')) {
            $where[] = ['sede_id', $user->sede_id];
            $orwhere[] = ['sede_id', $user->sede_id];
        } else { // administrador, sistema
            if ($filtro_sede_id != 0) {
                $where[] = ['sede_id', $filtro_sede_id];
                $orwhere[] = ['sede_id', $filtro_sede_id];
            }
        }

        // Consulta de clientes
        $clientes = Cliente::with(['user', 'equipo', 'sede', 'etapa', 'comentarios', 'movistars'])
            ->where($where)
            ->orWhere($orwhere)
            ->orderByDesc('fecha_gestion')
            ->paginate($paginate);

        // Conteo de clientes nuevos y gestionados
        $countClienteNuevo = 0;
        $countClienteGestionado = 0;

        $userIds = match (true) {
            $filtro_user_id != 0 => [$filtro_user_id],
            $user->hasRole(['sistema', 'gerente general', 'gerente comercial']) => null,
            $user->hasRole('supervisor') => $user->equipo->users->pluck('id')->toArray(),
            $user->hasRole('ejecutivo') => [$user->id],
            default => [],
        };

        // Función para aplicar filtro de usuarios si corresponde
        $filtrarPorUsuarios = function ($query) use ($userIds) {
            if (!is_null($userIds)) {
                $query->whereIn('user_id', $userIds);
            }
            return $query;
        };

        $countClienteNuevo = $filtrarPorUsuarios(
            Cliente::whereDate('fecha_nuevo', Carbon::today())
        )->count();

        $countClienteGestionado = $filtrarPorUsuarios(
            Cliente::whereDate('fecha_gestion', Carbon::today())
                ->where('etiqueta_id', '!=', 2)
        )->count();

        // Configuración adicional
        $config = Helpers::configuracionExcelJsonGet();

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
            'countClienteGestionado',
            'paginate'
        ));
    }

    /**
     * Método para contar clientes por etapa según el rol del usuario.
     */
    private function contarClientesPorEtapa($user, $filtro_equipo_id = null, $filtro_user_id = null, $filtro_sede_id = null, $filtro_fecha_desde = null, $filtro_fecha_hasta = null)
    {
        $rol = $user->roles->first()->name; // Obtener el rol del usuario
        $etapas = Etapa::where('estado', true)
            ->orderBy('orden')
            ->get();
        $conteoEtapas = [];

        foreach ($etapas as $etapa) {
            $query = Cliente::where('etapa_id', $etapa->id);

            // Aplicar filtro de sede si está seleccionado
            if ($filtro_sede_id && $filtro_sede_id != 0) {
                $query->where('sede_id', $filtro_sede_id);
            }

            // Aplicar filtro de equipo si está seleccionado
            if ($filtro_equipo_id && $filtro_equipo_id != 0) {
                $query->where('equipo_id', $filtro_equipo_id);
            }

            // Aplicar filtro de ejecutivo si está seleccionado
            if ($filtro_user_id && $filtro_user_id != 0) {
                $query->where('user_id', $filtro_user_id);
            }

            // Aplicar filtro de fechas si están seleccionadas
            if ($filtro_fecha_desde && $filtro_fecha_hasta) {
                $query->whereBetween('fecha_gestion', [
                    Carbon::parse($filtro_fecha_desde)->startOfDay(),
                    Carbon::parse($filtro_fecha_hasta)->endOfDay()
                ]);
            }

            // Filtrar según el rol del usuario (si no hay filtros aplicados)
            if ($rol === 'ejecutivo' && !$filtro_user_id && !$filtro_equipo_id && !$filtro_sede_id) {
                $query->where('user_id', $user->id);
            } elseif ($rol === 'supervisor' && !$filtro_equipo_id && !$filtro_sede_id) {
                $equipoIds = $user->equipo->users->pluck('id');
                $query->whereIn('user_id', $equipoIds);
            } elseif ($rol === 'jefe comercial' && !$filtro_sede_id) {
                $query->where('sede_id', $user->sede_id);
            }

            // Contar clientes en la etapa actual
            $conteoEtapas[$etapa->id] = [
                'nombre' => $etapa->nombre,
                'clientes_solo_count' => $query->count(),
                'color' => $etapa->color,
                'tooltip' => $etapa->tooltip ?? '',
            ];
        }

        return $conteoEtapas;
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
    public function show(Request $request, string $id)
    {
        $view = request('view');
        if ($view == 'show-validar-ruc') {
            $request->validate(
                [
                    'ruc' => 'numeric|digits:11|starts_with:20,10|unique:clientes,ruc|bail',
                ],
                [
                    'ruc.numeric' => 'El "Ruc" debe ser numérico.',
                    'ruc.digits' => 'El "Ruc" debe tener exactamente 11 dígitos.',
                    'ruc.starts_with' => 'El "Ruc" debe iniciar con 20 o 10.',
                    'ruc.unique' => 'El "Ruc" ya se encuentra registrado.',
                ]
            );
        } elseif ($view === 'show-editar-venta') {
            $venta = Venta::find($id);
            $productos = $venta->productos;

            return $productos;
        } elseif ($view === 'show-ultima-venta') {
            $cliente = Cliente::find($id);
            $productos = false;
            if (isset($cliente)) {
                $productos = isset($cliente->ventas->last()->productos) ? $cliente->ventas->last()->productos : false;
            }

            return $productos;
        } elseif ($view === 'show-select-sede') {
            $sede = Sede::find($id);
            if ($sede) {
                $equipos = Equipo::where('sede_id', $sede->id)->get();
                $users = User::role('ejecutivo')->where('sede_id', $sede->id)->get();
            } else {
                $equipos = Equipo::all();
                $users = User::role('ejecutivo')->get();
            }

            return response()->json([
                'equipos' => $equipos,
                'users' => $users,
            ]);
        } elseif ($view === 'show-select-equipo') {
            $equipo = Equipo::find($id);
            $users = $equipo->users;

            return response()->json([
                'users' => $users,
            ]);
        } elseif ($view === 'show-select-user') {
            $sede_id = request('sede_id');
            if ($sede_id) {
                $users = User::role('ejecutivo')->where('sede_id', $sede_id)->get();
            } else {
                $users = User::role('ejecutivo')->get();
            }

            return response()->json([
                'users' => $users,
            ]);
        } elseif ($view === 'show-data-select') {
            $sedes = Sede::all();
            $equipos = Equipo::all();
            $users = User::role('ejecutivo')->get();

            return response()->json([
                'sedes' => $sedes,
                'equipos' => $equipos,
                'users' => $users,
            ]);
        } elseif ($view === 'show-sucursal-select') {
            $sucursal = Sucursal::where('cliente_id', $id)->get();
            return response()->json($sucursal);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $view = request('view');
        if ($view === 'edit-asignar') {
            $equipos = Equipo::all();
            if (auth()->user()->hasRole('supervisor')) {
                $equipo = Equipo::find(auth()->user()->equipo->id);
                $ejecutivos = $equipo->users;
            } else {
                $ejecutivos = User::role('ejecutivo')->get();
            }
            $clients = request('clients');
            $etapas = Etapa::all();

            return view('sistema.cliente.gestion.asignar', compact(
                'equipos',
                'ejecutivos',
                'clients',
                'etapas',
            ));
        } elseif ($view === 'edit-detalle') {
            $cliente_id = $id;
            $user = auth()->user();
            $data = $this->clienteService->obtenerClienteDetalle($user, $cliente_id);

            return view('sistema.cliente.gestion.detalle', compact('data'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $view = request('view');
        $cliente = Cliente::find($id);
        if ($view === 'update-cliente') {
            $ruc = $request->input('ruc');
            $rules = [
                'ruc' => 'required|numeric|digits:11|starts_with:20,10|unique:clientes,ruc,' . $id . '|bail',
                'razon_social' => 'required|bail',
            ];
            // Condicional según prefijo del RUC
            if ($ruc && str_starts_with($ruc, '20')) {
                $rules['ciudad'] = 'required|bail';
                $rules['departamento_codigo'] = 'required|bail';
                $rules['provincia_codigo'] = 'required|bail';
                $rules['distrito_codigo'] = 'required|bail';
            } else {
                $rules['ciudad'] = 'nullable';
                $rules['departamento_codigo'] = 'nullable';
                $rules['provincia_codigo'] = 'nullable';
                $rules['distrito_codigo'] = 'nullable';
            }
            $messages = [
                'ruc.required' => 'El "Ruc" es obligatorio.',
                'ruc.numeric' => 'El "Ruc" debe ser numérico.',
                'ruc.digits' => 'El "Ruc" debe tener exactamente 11 dígitos.',
                'ruc.starts_with' => 'El "Ruc" debe iniciar con 20 o 10.',
                'ruc.unique' => 'El "Ruc" ya se encuentra registrado.',
                'razon_social.required' => 'La "Razón Social" es obligatorio.',
                'ciudad.required' => 'La "Ciudad" es obligatoria.',
                'departamento_codigo.required' => 'El "Departamento" es obligatorio.',
                'provincia_codigo.required' => 'La "Provincia" es obligatoria.',
                'distrito_codigo.required' => 'El "Distrito" es obligatorio.',
            ];
            $cliente->ruc = request('ruc');
            $cliente->razon_social = request('razon_social');
            $cliente->estado = request('estado');
            $cliente->condicion = request('condicion');
            $cliente->actividad_economica = request('actividad_economica');
            $cliente->ciudad = request('ciudad') ?? '-';
            $cliente->departamento_codigo = request('departamento_codigo');
            $cliente->provincia_codigo = request('provincia_codigo');
            $cliente->distrito_codigo = request('distrito_codigo');
            $cliente->generado_bot = filter_var(request('generado_bot'), FILTER_VALIDATE_BOOLEAN);
            $cliente->save();
            $this->clienteService->exportclienteStore($cliente->id);

            return response()->json($cliente);
        } elseif ($view === 'update-contacto') {
            $request->validate(
                [
                    'nombre' => 'required|bail',
                    'dni' => 'required|numeric|digits:8|bail',
                    'celular' => 'required|bail',
                    'cargo' => 'required|bail',
                ],
                [
                    'nombre.required' => 'El "Nombre" es obligatorio.',
                    'dni.required' => 'El "DNI" es obligatorio.',
                    'dni.numeric' => 'El "DNI" debe ser numérico.',
                    'dni.digits' => 'El "DNI" debe tener exactamente 8 dígitos.',
                    'celular.required' => 'El "Celular" es obligatorio.',
                    'cargo.required' => 'El "Cargo" es obligatorio.',
                ]
            );
            if (request('contacto_id') != '') {
                $contacto = Contacto::find(request('contacto_id'));
            } else {
                $contacto = new Contacto;
            }
            $contacto->dni = request('dni');
            $contacto->nombre = request('nombre');
            $contacto->celular = request('celular');
            $contacto->cargo = request('cargo');
            $contacto->correo = request('correo') ?? '';
            $contacto->fecha_ultimo = now();
            $contacto->fecha_proximo = now();
            $contacto->cliente_id = $cliente->id;
            $contacto->save();

            $data_contactos = $cliente->contactos()->orderBy('contactos.id', 'desc')->limit(8)->get();
            $contactos = [];
            foreach ($data_contactos as $value) {
                $contactos[] = [
                    'id' => $value->id,
                    'dni' => $value->dni,
                    'nombre' => $value->nombre,
                    'celular' => $value->celular,
                    'cargo' => $value->cargo,
                    'correo' => $value->correo,
                ];
            }
            $this->clienteService->exportclienteStore($cliente->id);

            return response()->json($contactos);
        } elseif ($view === 'update-sucursal') {
            $request->validate(
                [
                    'sucursal_nombre' => 'required|bail',
                    'sucursal_direccion' => 'required|bail',
                    'sucursal_departamento_codigo' => 'required|bail',
                    'sucursal_provincia_codigo' => 'required|bail',
                    'sucursal_distrito_codigo' => 'required|bail',
                ],
                [
                    'sucursal_nombre.required' => 'El "Nombre de Sucursal" es obligatorio.',
                    'sucursal_direccion.required' => 'La "Dirección de Sucursal" es obligatoria.',
                    'sucursal_departamento_codigo.required' => 'El "Departamento de Sucursal" es obligatorio.',
                    'sucursal_provincia_codigo.required' => 'La "Provincia de Sucursal" es obligatoria.',
                    'sucursal_distrito_codigo.required' => 'El "Distrito de Sucursal" es obligatorio.',
                ]
            );
            if (request('sucursal_id') != '') {
                $sucursal = Sucursal::find(request('sucursal_id'));
            } else {
                $sucursal = new Sucursal;
            }
            $sucursal->nombre = request('sucursal_nombre');
            $sucursal->direccion = request('sucursal_direccion');
            $sucursal->facilidad_tecnica = filter_var(request('sucursal_facilidad_tecnica'), FILTER_VALIDATE_BOOLEAN);
            $sucursal->departamento_codigo = request('sucursal_departamento_codigo');
            $sucursal->provincia_codigo = request('sucursal_provincia_codigo');
            $sucursal->distrito_codigo = request('sucursal_distrito_codigo');
            $sucursal->cliente_id = $cliente->id;
            $sucursal->save();

            $data_sucursales = $cliente->sucursales()->orderBy('sucursals.id', 'desc')->limit(8)->get();
            $sucursales = [];
            foreach ($data_sucursales as $value) {
                $departamentoNombre = Departamento::where('codigo', $value->departamento_codigo)->first()->nombre ?? '';
                $provinciaNombre = Provincia::where('codigo', $value->provincia_codigo)->first()->nombre ?? '';
                $distritoNombre = Distrito::where('codigo', $value->distrito_codigo)->first()->nombre ?? '';
                $ubigeo = $departamentoNombre.' - '.$provinciaNombre.' - '.$distritoNombre;
                $sucursales[] = [
                    'id' => $value->id,
                    'nombre' => $value->nombre,
                    'direccion' => $value->direccion,
                    'facilidad_tecnica' => $value->facilidad_tecnica,
                    'departamento_codigo' => $value->departamento_codigo,
                    'provincia_codigo' => $value->provincia_codigo,
                    'distrito_codigo' => $value->distrito_codigo,
                    'ubigeo' => $ubigeo,
                ];
            }
            $this->clienteService->exportclienteStore($cliente->id);

            return response()->json($sucursales);
        } elseif ($view === 'update-comentario') {
            $request->validate(
                [
                    'comentario' => 'required|bail',
                ],
                [
                    'comentario.required' => 'El "Comentario" es obligatorio.',
                ]
            );
            $etapa = Etapa::find($cliente->etapa_id);
            $comentario = new Comentario;
            $comentario->comentario = request('comentario');
            $comentario->detalle = $etapa->nombre;
            $comentario->user_id = auth()->user()->id;
            $comentario->cliente_id = $cliente->id;
            $comentario->etiqueta_id = 4; // etiqueta_id, 4=gestionado;
            $comentario->save();

            $data_comentarios = $cliente->comentarios()->where('user_id', auth()->user()->id)->orderBy('comentarios.id', 'desc')->get();
            $comentarios = [];
            foreach ($data_comentarios as $value) {
                $comentarios[] = [
                    'id' => $value->id,
                    'comentario' => $value->comentario,
                    'usuario' => $value->user->name,
                    'fecha' => $value->created_at->format('d-m-Y h:i:s A'),
                    'etiqueta' => $value->etiqueta->nombre,
                    'detalle' => $value->detalle,
                ];
            }
            // cliente
            $cliente->fecha_gestion = now();
            $cliente->etiqueta_id = 4; // gestionado
            $cliente->save();
            $this->clienteService->exportclienteStore($cliente->id);

            return response()->json($comentarios);
        } elseif ($view === 'update-movistar') {
            $movistar = new Movistar;
            $movistar->linea_claro = request('linea_claro');
            $movistar->linea_entel = request('linea_entel');
            $movistar->linea_bitel = request('linea_bitel');
            $movistar->linea_movistar = request('linea_movistar');
            $movistar->score = request('score');
            $movistar->cantidad_trabajador = request('cantidad_trabajador');
            $movistar->cantidad_sucursal = request('cantidad_sucursal');
            $movistar->estadowick_id = null;
            $movistar->estadodito_id = 1;
            $movistar->clientetipo_id = 1;
            $movistar->ejecutivo_salesforce = '';
            $movistar->agencia_id = 1;
            $movistar->cliente_id = $cliente->id;
            $movistar->save();
            $this->clienteService->exportclienteStore($cliente->id);
        } elseif ($view === 'update-etapa') {
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
            $cliente->etapas()->attach(request('etapa_id'));
            $cliente->fecha_gestion = now();
            $cliente->etiqueta_id = 4; // gestionado
            $cliente->etapa_id = request('etapa_id');
            $cliente->save();

            $etapa = Etapa::find(request('etapa_id'));
            $comentario = new Comentario;
            $comentario->comentario = request('comentario');
            $comentario->detalle = 'Cambio de etapa a ' . $etapa->nombre;
            $comentario->user_id = auth()->user()->id;
            $comentario->cliente_id = $cliente->id;
            $comentario->save();

            $data_comentarios = $cliente->comentarios()->where('user_id', auth()->user()->id)->orderBy('comentarios.id', 'desc')->limit(5)->get();
            $comentarios = [];
            foreach ($data_comentarios as $value) {
                $comentarios[] = [
                    'id' => $value->id,
                    'comentario' => $value->comentario,
                    'usuario' => $value->user->name,
                    'fecha' => $value->created_at->format('d-m-Y h:i:s A'),
                    'etiqueta' => $value->etiqueta->nombre,
                    'detalle' => $value->detalle,
                ];
            }
            $this->clienteService->exportclienteStore($cliente->id);

            return response()->json($comentarios);
        } elseif ($view === 'update-cargo') {
            $ventas = Venta::find(request('venta_id'));
            if (isset($ventas)) {
                foreach ($ventas->productos as $value) {
                    DB::table('producto_venta')->where('venta_id', $value->pivot->venta_id)->delete();
                }
            }
            if (! is_null(request('dataCargo'))) {
                $venta_total = 0;
                $venta = new Venta;
                $venta->cliente_id = $cliente->id;
                $venta->user_id = auth()->user()->id;
                $venta->save();
                foreach (request('dataCargo') as $row) {
                    $venta->productos()->attach($row['producto_id'], [
                        'producto_nombre' => $row['producto_nombre'],
                        'detalle' => $row['detalle'] ?? '',
                        'cantidad' => $row['cantidad'],
                        'precio' => $row['precio'],
                        'total' => $row['total'],
                        'sucursal_nombre' => $row['sucursal_nombre'] ?? null,
                        'sucursal_id' => $row['sucursal_id'] ?? null,
                    ]);
                    $venta_total += $row['total'];
                }
                $venta->total = $venta_total;
                $venta->save();
            }
            $this->clienteService->exportclienteStore($cliente->id);

            return $venta->productos;
        } elseif ($view === 'update-asignar') {
            $request->validate(
                [
                    'user_id' => 'required|bail',
                    'etapa_id' => 'required|bail',
                ],
                [
                    'user_id.required' => 'El "Ejecutivo" es obligatorio.',
                    'etapa_id.required' => 'La "Etapa" es obligatorio.',
                ]
            );
            // cliente
            $executive = User::find(request('user_id'));
            $clients = request('clients');
            foreach ($clients as $value) {
                $client = Cliente::find($value);
                $client->fecha_gestion = now();
                $client->fecha_nuevo = now();
                $client->etiqueta_id = 2; // asignado
                $client->user_id = $executive->id;
                $client->equipo_id = $executive->equipos->last()->id;
                $client->sede_id = $executive->sede_id;
                $client->etapa_id = request('etapa_id');
                $client->save();
                $client->usersHistorial()->attach($executive->id);
                $client->etapas()->attach(1);
                // comentario
                $etapa = Etapa::find(request('etapa_id'));
                $comentario = new Comentario;
                $comentario->comentario = 'Cliente asignado.';
                $comentario->detalle = 'Cambio de etapa a ' . $etapa->nombre;
                $comentario->cliente_id = $client->id;
                $comentario->user_id = auth()->user()->id;
                $comentario->etiqueta_id = 2; // etiqueta_id, 2=asignado;
                $comentario->save();
                $this->clienteService->exportclienteStore($client->id);
            }
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

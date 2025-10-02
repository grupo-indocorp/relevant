<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Comentario;
use App\Models\Contacto;
use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Equipo;
use App\Models\Etapa;
use App\Models\Exportcliente;
use App\Models\Movistar;
use App\Models\Provincia;
use App\Models\Sucursal;
use App\Models\Venta;

class ClienteService
{
    public function listarClientesPorRol($user)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 120);
        if ($user->hasRole(['administrador', 'gerente comercial'])) {
            $clientes = Cliente::with(['etapas', 'user', 'user.equipos', 'comentarios'])->orderByDesc('id')->get();
        } elseif ($user->hasRole(['supervisor'])) {
            $ejecutivos = [];
            $equipo = Equipo::find($user->equipo->id);
            foreach ($equipo->users as $ejecutivo) {
                if ($ejecutivo->equipos()->orderByDesc('pivot_id')->first()->id == $equipo->id) {
                    array_push($ejecutivos, $ejecutivo->id);
                }
            }
            $clientes = Cliente::with(['etapas', 'user', 'user.equipos', 'comentarios'])->whereIn('user_id', $ejecutivos)->orderByDesc('id')->get();
        } elseif ($user->hasRole(['ejecutivo'])) {
            $clientes = $user->clientes()->with(['etapas', 'user', 'user.equipos', 'comentarios'])->orderByDesc('id')->get();
        } else {
            $clientes = $user->clientes()->with(['etapas', 'user', 'user.equipos', 'comentarios'])->orderByDesc('id')->get();
        }

        return $clientes;
    }

    public function conteoClientesPorEtapa($user)
    {
        $clientes_data = $this->listarClientesPorRol($user);
        $countData = [];
        $conteoClientesPorEtapa = [];
        foreach ($clientes_data as $cliente) {
            // $etapaId = $cliente->etapas()->orderByDesc('pivot_id')->first()->id;
            $etapaId = $cliente->etapas->last()->id;
            if (! isset($countData[$etapaId])) {
                $countData[$etapaId] = 1;
            } else {
                $countData[$etapaId]++;
            }
        }
        foreach ($countData as $key => $value) {
            $etapa = Etapa::find($key);
            $conteoClientesPorEtapa[] = [
                'id' => $etapa->id,
                'nombre' => $etapa->nombre,
                'nombre-count' => "$etapa->nombre ($value)",
                'count' => $value,
                'color' => $etapa->color,
            ];
        }

        return $conteoClientesPorEtapa;
    }

    public function ultimaGestionCliente($comentario) {}

    public function organizarListaCliente($data, $etapa_id = 0)
    {
        $clientes = [];
        if ($etapa_id != 0) {
            foreach ($data as $cliente) {
                // $etapa = $cliente->etapas()->orderByDesc('pivot_id')->first();
                $etapa = $cliente->etapas->last();
                if ($etapa->id == $etapa_id) {
                    $comentario = $cliente->comentarios->last();
                    $fecha_ultimagestion = $comentario->created_at ?? $cliente->created_at;
                    $dias = $fecha_ultimagestion->diffInDays(now());
                    $clientes[] = [
                        'id' => $cliente->id,
                        'ruc' => $cliente->ruc,
                        'razon_social' => $cliente->razon_social,
                        'equipo' => $cliente->user->equipos->last()->nombre ?? '',
                        'ejecutivo' => $cliente->user->name ?? '',
                        'etapa_id' => $etapa->id,
                        'etapa' => $etapa->nombre,
                        'color' => $etapa->color,
                        'opacity' => $etapa->opacity,
                        'comentario' => $comentario->comentario ?? '',
                        'fecha_gestion' => $fecha_ultimagestion->format('d/m/Y H:i:s A'),
                        'dias' => $dias,
                        'cliente' => $cliente,
                    ];
                }
            }
        } else {
            foreach ($data as $cliente) {
                $comentario = $cliente->comentarios->last();
                $fecha_ultimagestion = $comentario->created_at ?? $cliente->created_at;
                $dias = $fecha_ultimagestion->diffInDays(now());
                // $etapa = $cliente->etapas()->latest('pivot_id')->first();
                $etapa = $cliente->etapas->last();
                $clientes[] = [
                    'id' => $cliente->id,
                    'ruc' => $cliente->ruc,
                    'razon_social' => $cliente->razon_social,
                    'equipo' => $cliente->user->equipos->last()->nombre ?? '',
                    'ejecutivo' => $cliente->user->name ?? '',
                    'etapa_id' => $etapa->id,
                    'etapa' => $etapa->nombre,
                    'color' => $etapa->color,
                    'opacity' => $etapa->opacity,
                    'comentario' => $comentario->comentario ?? '',
                    'fecha_gestion' => $fecha_ultimagestion->format('d/m/Y H:i:s A'),
                    'dias' => $dias,
                    'cliente' => $cliente,
                ];
            }
        }

        return $clientes;
    }

    public function listarClientes($user)
    {
        $data = $this->listarClientesPorRol($user);
        $clientes = $this->organizarListaCliente($data);

        return $clientes;
    }

    public function listarClientesPorEtapa($user, $etapa_id)
    {
        $data = $this->listarClientesPorRol($user);
        $clientes = $this->organizarListaCliente($data, $etapa_id);

        return $clientes;
    }

    public function consultorCliente($busqueda)
    {
        $cliente = Cliente::with(['user', 'equipo', 'etapa'])->where('ruc', $busqueda)->first();

        return $cliente;
    }

    public function obtenerClienteDetalle($user, $cliente_id)
    {
        $cliente = Cliente::find($cliente_id);
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
                'fecha_proximo' => $value->fecha_proximo,
            ];
        }

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

        $sistema = $user->hasRole('sistema');
        $gerente_general = $user->hasRole('gerente general');
        $gerente_comercial = $user->hasRole('gerente comercial');
        $asistente_comercial = $user->hasRole('asistente comercial');
        $jefe_comercial = $user->hasRole('jefe comercial');
        $supervisor = $user->hasRole('supervisor');
        $capacitador = $user->hasRole('capacitador');
        $planificacion = $user->hasRole('planificacion');

        // if ($sistema || $gerente_general || $gerente_comercial || $asistente_comercial || $capacitador || $planificacion) {
            $data_comentarios = $cliente->comentarios()->orderBy('comentarios.id', 'desc')->get();
        // } elseif ($supervisor || $jefe_comercial) {
        //     $data_comentarios = $cliente->comentarios()->orderBy('comentarios.id', 'desc')->limit(5)->get();
        // } else {
        //     $data_comentarios = $cliente->comentarios()->where('user_id', $user->id)->orderBy('comentarios.id', 'desc')->limit(5)->get();
        // }
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
        $data_notificacions = $cliente->notificacions()->orderBy('notificacions.id', 'desc')->limit(4)->get();
        $notificacions = $data_notificacions != '[]' ? $data_notificacions : false;
        $data = [
            'cliente' => $cliente,
            'contactos' => $contactos,
            'sucursales' => $sucursales,
            'comentarios' => $comentarios,
            'movistar' => $cliente->movistars->last(),
            'ventas' => $cliente->ventas->last(),
            'notificacion' => $notificacions,
        ];

        return $data;
    }

    public function etapasConConteo()
    {
        $where = [];
        $user = auth()->user();
        if ($user->hasRole('ejecutivo')) {
            $where[] = ['user_id', $user->id];
        } elseif ($user->hasRole('supervisor')) {
            $where[] = ['equipo_id', $user->equipo->id];
        } elseif ($user->hasRole('jefe comercial')) {
            $where[] = ['sede_id', $user->sede_id];
        }

        $countData = [];
        /* $clientes_data = Cliente::with(['user', 'equipo', 'sede', 'etapa', 'comentarios'])->where($where)->get();
        foreach ($clientes_data as $cliente) {
            $etapaId = $cliente->etapa_id;
            if (!isset($countData[$etapaId])) {
                $countData[$etapaId] = 1;
            } else {
                $countData[$etapaId]++;
            }
        } */

        $count_total = 0;
        $data_etapas = [];
        $etapas = Etapa::all();
        foreach ($etapas as $value) {
            if (isset($countData[$value->id])) {
                $count_total = $count_total + $countData[$value->id];
                $data_etapas[] = [
                    'id' => $value->id,
                    'nombre' => $value->nombre,
                    'color' => $value->color,
                    'clientes_solo_count' => $countData[$value->id],
                ];
            } else {
                $data_etapas[] = [
                    'id' => $value->id,
                    'nombre' => $value->nombre,
                    'color' => $value->color,
                    'clientes_solo_count' => 0,
                ];
            }
        }
        $data = [
            'data_etapas' => $data_etapas,
            'count_total' => $count_total,
        ];

        return $data;
    }

    public function clientesPorRol($user)
    {
        $where = [];
        if ($user->hasRole('ejecutivo')) {
            $where[] = ['user_id', $user->id];
        } elseif ($user->hasRole('supervisor')) {
            $where[] = ['equipo_id', $user->equipo->id];
        } elseif ($user->hasRole('invitado')) {
            $where = [];
        } else {
            $where = [];
        }
        $clientes = Cliente::with(['user', 'equipo', 'sede', 'etapa', 'comentarios'])->where($where)->paginate(2);

        return $clientes;
    }

    // Registrar Cliente
    public function clienteStore()
    {
        $user = auth()->user();
        // Ciente
        $cliente = new Cliente;
        $cliente->ruc = request('ruc');
        $cliente->razon_social = request('razon_social');
        $cliente->ciudad = request('ciudad');
        $cliente->fecha_gestion = now();
        $cliente->fecha_nuevo = now();
        $cliente->etiqueta_id = 1; // nuevo
        $cliente->user_id = $user->id;
        $cliente->equipo_id = $user->equipos->last()->id ?? 1;
        $cliente->sede_id = $user->equipos->last()->sede->id ?? 1;
        $cliente->etapa_id = request('etapa_id');
        $cliente->contactabilidad = filter_var(request('contactabilidad'), FILTER_VALIDATE_BOOLEAN);
        $cliente->departamento_codigo = request('departamento_codigo');
        $cliente->provincia_codigo = request('provincia_codigo');
        $cliente->distrito_codigo = request('distrito_codigo');
        $cliente->save();
        $cliente->usersHistorial()->attach($user->id);
        $cliente->etapas()->attach(1);
        // Contacto
        if (request('dni') != '') {
            $contacto = new Contacto;
            $contacto->dni = request('dni');
            $contacto->nombre = request('nombre');
            $contacto->celular = request('celular') ?? '';
            $contacto->cargo = request('cargo');
            $contacto->correo = request('correo') ?? '';
            $contacto->fecha_ultimo = now();
            $contacto->fecha_proximo = now();
            $contacto->cliente_id = $cliente->id;
            $contacto->save();
        }
        // Sucursal
        if (request('sucursal_nombre') != '') {
            $sucursal = new Sucursal();
            $sucursal->nombre = request('sucursal_nombre');
            $sucursal->direccion = request('sucursal_direccion');
            $sucursal->facilidad_tecnica = filter_var(request('sucursal_facilidad_tecnica'), FILTER_VALIDATE_BOOLEAN);
            $sucursal->departamento_codigo = request('sucursal_departamento_codigo');
            $sucursal->provincia_codigo = request('sucursal_provincia_codigo');
            $sucursal->distrito_codigo = request('sucursal_distrito_codigo');
            $sucursal->cliente_id = $cliente->id;
            $sucursal->save();
        }
        // Comentario
        $etapa = Etapa::find(request('etapa_id'));
        $comentario = new Comentario;
        $comentario->comentario = request('comentario');
        $comentario->detalle = 'Cambio de etapa a '.$etapa->nombre;
        $comentario->user_id = $user->id;
        $comentario->cliente_id = $cliente->id;
        $comentario->etiqueta_id = 1; // etiqueta_id, 1=nuevo;
        $comentario->save();
        // Movistar
        $movistar = new Movistar;
        $movistar->linea_claro = request('linea_claro') ?? 0;
        $movistar->linea_entel = request('linea_entel') ?? 0;
        $movistar->linea_bitel = request('linea_bitel') ?? 0;
        $movistar->linea_movistar = request('linea_movistar') ?? 0;
        $movistar->estadowick_id = request('estadowick_id') ?? null;
        $movistar->estadodito_id = request('estadodito_id') ?? 1;
        $movistar->clientetipo_id = request('clientetipo_id') ?? 1;
        $movistar->ejecutivo_salesforce = request('ejecutivo_salesforce') ?? '';
        $movistar->agencia_id = request('agencia_id') ?? 1;
        $movistar->cliente_id = $cliente->id;
        $movistar->save();
        // Etapa
        $cliente->etapas()->attach(request('etapa_id'));
        // Cargo
        // if (! is_null(request('dataCargo'))) {
            // $venta_total = 0;
            // $venta = new Venta;
            // $venta->cliente_id = $cliente->id;
            // $venta->user_id = $user->id;
            // $venta->save();
            // foreach (request('dataCargo') as $row) {
            //     $venta->productos()->attach($row['producto_id'], [
            //         'producto_nombre' => $row['producto_nombre'],
            //         'detalle' => $row['detalle'] ?? '',
            //         'cantidad' => $row['cantidad'],
            //         'precio' => $row['precio'],
            //         'total' => $row['total'],
            //         'sucursal_nombre' => $row['sucursal_nombre'],
            //         'sucursal_id' => $row['sucursal_id'],
            //     ]);
            //     $venta_total += $row['total'];
            // }
            // $venta->total = $venta_total;
            // $venta->save();
        // }

        // Registrar en exportcliente
        $this->exportclienteStore($cliente->id);
    }

    // Registrar Exportcliente en paralelo al registrar un nuevo cliente
    public function exportclienteStore($cliente_id)
    {
        $cliente = Exportcliente::where('cliente_id', $cliente_id)->first();
        if ($cliente) {
            Exportcliente::find($cliente->id)->delete();
        }
        $value = Cliente::find($cliente_id);
        $ventas = $value->ventas->last();
        $m_cant = 0;
        $m_carf = 0;
        $f_cant = 0;
        $f_carf = 0;
        $a_cant = 0;
        $a_carf = 0;
        if ($ventas) {
            // 2 = movil, 3 = fija, 4 = avanzada
            foreach ($ventas->productos as $item) {
                if ($item->categoria_id === 2) {
                    $m_cant += $item->pivot->cantidad;
                    $m_carf += $item->pivot->total;
                } elseif ($item->categoria_id === 3) {
                    $f_cant += $item->pivot->cantidad;
                    $f_carf += $item->pivot->total;
                } elseif ($item->categoria_id === 4) {
                    $a_cant += $item->pivot->cantidad;
                    $a_carf += $item->pivot->total;
                }
            }
        }

        // productos
        $ingresos = 0;
        $precio = 0;
        $qLinea = 0;
        $producto = '';
        $tipo = '';
        $venta_id = 0;
        if (isset($value->ventas->last()->productos)) {
            $venta = $value->ventas->last();
            $venta_id = $venta->id;
            $ingresos = $venta->total;
            $producto = $venta->productos->first()->categoria->nombre ?? '';
            $tipo = $venta->productos->first()->nombre ?? '';
            foreach ($venta->productos as $item_produc) {
                $precio += $item_produc->pivot->precio;
                $qLinea += $item_produc->pivot->cantidad;
            }
        }

        // Comentarios
        $comentarios = $value->comentarios()->latest()->take(5)->get();
        $comentariosArray = $comentarios->toArray();
        $textoPredeterminado = '';
        while (count($comentariosArray) < 5) {
            $comentariosArray[] = ['comentario' => $textoPredeterminado];
        }

        $exportCliente = new Exportcliente;
        $exportCliente->ruc = $value->ruc;
        $exportCliente->razon_social = $value->razon_social;
        $exportCliente->ciudad = $value->ciudad;
        $exportCliente->fecha_creacion = $value->created_at->format('Y-m-d');
        $exportCliente->fecha_blindaje = now()->format('Y-m-d');
        $exportCliente->fecha_primer_contacto = $value->created_at->format('Y-m-d');
        $exportCliente->fecha_ultimo_contacto = date('Y-m-d', strtotime($value->fecha_gestion));
        $exportCliente->fecha_proximo_contacto = now()->format('Y-m-d');
        $exportCliente->direccion_instalacion = '';
        $exportCliente->producto = $tipo;
        $exportCliente->producto_ultimo_registro = $tipo;
        $exportCliente->producto_categoria = $producto;
        $exportCliente->producto_total_cantidad = $qLinea;
        $exportCliente->producto_total_precio = $precio;
        $exportCliente->producto_total_total = $ingresos;
        $exportCliente->producto_categoria_1 = $m_cant;
        $exportCliente->producto_categoria_1_total = $m_carf;
        $exportCliente->producto_categoria_2 = $f_cant;
        $exportCliente->producto_categoria_2_total = $f_carf;
        $exportCliente->producto_categoria_3 = $a_cant;
        $exportCliente->producto_categoria_3_total = $a_carf;
        $exportCliente->producto_categoria_4 = '0';
        $exportCliente->producto_categoria_4_total = '0';
        $exportCliente->contacto = $value->contactos->last()->nombre ?? '';
        $exportCliente->contacto_dni = $value->contactos->last()->dni ?? '';
        $exportCliente->contacto_cargo = $value->contactos->last()->cargo ?? '';
        $exportCliente->contacto_email = $value->contactos->last()->correo ?? '';
        $exportCliente->contacto_celular = $value->contactos->last()->celular ?? '';
        $exportCliente->ejecutivo = $value->user->name;
        $exportCliente->ejecutivo_equipo = $value->equipo->nombre;
        $exportCliente->ejecutivo_sede = $value->sede->nombre;
        $exportCliente->etapa = $value->etapa->nombre;
        $exportCliente->etapa_blindaje = $value->etapa->blindaje;
        $exportCliente->etapa_avance = $value->etapa->avance;
        $exportCliente->etapa_probabilidad = $value->etapa->probabilidad;
        $exportCliente->comentario_5 = $comentariosArray[0]['comentario'];
        $exportCliente->comentario_4 = $comentariosArray[1]['comentario'];
        $exportCliente->comentario_3 = $comentariosArray[2]['comentario'];
        $exportCliente->comentario_2 = $comentariosArray[3]['comentario'];
        $exportCliente->comentario_1 = $comentariosArray[4]['comentario'];
        $exportCliente->estado_wick = $value->movistars->last()->estadowick->nombre ?? '';
        $exportCliente->estado_dito = $value->movistars->last()->estadodito->nombre ?? '';
        $exportCliente->lineas_claro = $value->movistars->last()->linea_claro ?? '0';
        $exportCliente->lineas_entel = $value->movistars->last()->linea_entel ?? '0';
        $exportCliente->lineas_bitel = $value->movistars->last()->linea_bitel ?? '0';
        $exportCliente->lineas_movistar = $value->movistars->last()->linea_movistar ?? '0';
        $exportCliente->cliente_tipo = $value->movistars->last()->clientetipo->nombre ?? '';
        $exportCliente->agencia = $value->movistars->last()->agencia->nombre ?? '';
        $exportCliente->cliente_id = $value->id;
        $exportCliente->venta_id = $venta_id;
        $exportCliente->ejecutivo_id = $value->user->id;
        $exportCliente->ejecutivo_equipo_id = $value->equipo->id;
        $exportCliente->ejecutivo_sede_id = $value->sede->id;
        $exportCliente->etapa_id = $value->etapa->id;
        $exportCliente->save();
    }

    /**
     * return array $clientes
     */
    public function gestionCliente()
    {
        $clientes = Cliente::join('equipos', 'equipos.id', '=', 'clientes.equipo_id')
            ->select(
                'id',
                'ruc',
                'razon_social',
                'fecha_gestion',
            )
            // ->with(['equipo:id,nombre'])
            ->orderByDesc('fecha_gestion')
            ->get();

        // with(['user', 'equipo', 'sede', 'etapa', 'comentarios', 'movistars'])
        // ->where($where)
        // ->orWhere($orwhere)
        // ->limit(1)
        return $clientes;
    }
}

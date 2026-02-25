<x-sistema.tabla-contenedor>
    
    <table class="table align-items-center mb-0 ml-0 mr-0 p-1" id="gestion_cliente">
        <thead>
            <tr>
                @can('sistema.gestion_cliente.asignar')
                    <th>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="selectAllClients">
                        </div>
                    </th>
                @endcan
                <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Razón Social / RUC</th>

                @role(['sistema', 'gerente general', 'gerente comercial', 'asistente comercial', 'jefe comercial',
                    'supervisor', 'capacitador', 'planificacion'])
                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">EECC</th>
                @endrole

                <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Etapa</th>

                @role(['sistema', 'gerente general', 'gerente comercial', 'asistente comercial', 'capacitador',
                    'planificacion'])
                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Sede</th>
                @endrole

                <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Comentario</th>
                <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Fecha de última Gestión</th>
                <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Tipo de Base</th>
                <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Días sin Gestión</th>
                <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Fecha de Agenda</th>
                @role([
                    'sistema',
                    'gerente general',
                    'gerente comercial',
                    'asistente comercial',
                    'supervisor',
                    'capacitador',
                    'planificacion'])
                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Etiqueta</th>
                @endrole
            </tr>
        </thead>
        <tbody>
            @foreach ($clientes as $value)
                @php
                    $total_lineas =
                        ($value->movistars->last()->linea_claro ?? 0) +
                        ($value->movistars->last()->linea_entel ?? 0) +
                        ($value->movistars->last()->linea_bitel ?? 0);
                    $comentario = $value->comentarios->last();
                    $fecha_gestion = \Carbon\Carbon::parse($value->fecha_gestion)->startOfDay();
                    $dias = $fecha_gestion->diffInDays(\Carbon\Carbon::now()->startOfDay());
                @endphp
                <tr id="{{ $value->id }}">
                    @can('sistema.gestion_cliente.asignar')
                        <td>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="{{ $value->id }}">
                            </div>
                        </td>
                    @endcan
                    <td class="align-middle text-center">
                        <p class="text-xs font-weight-bold mb-0 uppercase">{{ substr($value->razon_social, 0, 30) }}</p>
                        <h6 class="mb-0 text-xs hover:cursor-pointer" onclick="detalleCliente({{ $value->id }})">
                            {{ $value->ruc }}
                        </h6>
                    </td>

                    @role(['sistema', 'gerente general', 'gerente comercial', 'asistente comercial', 'jefe comercial',
                        'supervisor', 'capacitador', 'planificacion'])
                        <td class="align-middle text-center">
                            <p class="text-xs font-weight-bold mb-0">{{ $value->equipo->nombre }}</p>
                            <p class="text-xs text-secondary mb-0">{{ $value->user->name }}</p>
                        </td>
                    @endrole

                    <td class="align-middle text-center">
                        <span class="text-xs font-semibold font-se mb-0 px-3 py-1 rounded-lg"
                            style="background-color: {{ $value->etapa->opacity }};">
                            {{ $value->etapa->nombre }}
                        </span>
                    </td>

                    @role(['sistema', 'gerente general', 'gerente comercial', 'asistente comercial', 'capacitador',
                        'planificacion'])
                        <td class="align-middle text-center">
                            <span class="text-xs font-weight-bold mb-0 uppercase">{{ $value->sede->nombre }}</span>
                        </td>
                    @endrole

                    <td class="align-middle text-center">
                        <span
                            class="text-secondary text-xs font-weight-normal">{{ substr($comentario->comentario ?? '', 0, 40) }}</span>
                    </td>
                    <td class="align-middle text-center">
                        <span
                            class="text-secondary text-xs font-weight-normal">{{ date('d/m/Y H:i:s A', strtotime($value->fecha_gestion)) }}</span>
                    </td>
                    <td class="align-middle text-center">
                        <span class="text-xs font-weight-bold mb-0 uppercase">
                            {{ $value->tipobase }}
                        </span>
                    </td>
                    <td class="align-middle text-center text-sm">
                        @if ($dias >= 60)
                            <span
                                class="text-xs font-weight-bold mb-0 px-3 py-1 rounded-lg bg-red-100">{{ $dias }}</span>
                        @else
                            <span
                                class="text-xs font-weight-bold mb-0 px-3 py-1 rounded-lg bg-green-100">{{ $dias }}</span>
                        @endif
                    </td>
                    <td class="align-middle text-center">
                        <span class="text-secondary text-xs font-weight-normal">
                            @php
                                $notificacion = $value->notificacions->last();
                                $fechaAgenda = optional($notificacion)->fecha;
                                $horaAgenda = optional($notificacion)->hora;
                                $formattedDateTime = '';

                                if ($fechaAgenda) {
                                    if ($horaAgenda) {
                                        $formattedDateTime = date('d/m/Y H:i A', strtotime($fechaAgenda . ' ' . $horaAgenda));
                                    } else {
                                        $formattedDateTime = date('d/m/Y', strtotime($fechaAgenda));
                                    }
                                }
                            @endphp
                            {{ $formattedDateTime }}
                        </span>
                    </td>

                    @role([
                        'sistema',
                        'gerente general',
                        'gerente comercial',
                        'asistente comercial',
                        'supervisor',
                        'capacitador',
                        'planificacion'])
                        <td class="align-middle text-center">
                            @php
                                $fechaGestion = Carbon\Carbon::parse($value->fecha_gestion)->toDateString();
                                $fechaNuevo = Carbon\Carbon::parse($value->fecha_nuevo)->toDateString();
                                $fechaHoy = Carbon\Carbon::today()->toDateString();
                            @endphp
                            @if ($fechaGestion == $fechaHoy)
                                @if ($value->etiqueta_id != 2)
                                    <span
                                        class="bg-slate-300 text-slate-700 text-xs font-semibold font-se mb-0 mx-1 px-3 py-1 rounded-lg">
                                        gestionado
                                    </span>
                                @endif
                            @endif
                            @if ($fechaNuevo == $fechaHoy)
                                <span
                                    class="bg-slate-300 text-slate-700 text-xs font-semibold font-se mb-0 mx-1 px-3 py-1 rounded-lg">
                                    nuevo
                                </span>
                            @endif
                        </td>
                    @endrole
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $clientes->appends([
            'filtro_ruc' => $filtro['filtro_ruc'],
            'filtro_etapa_id' => $filtro['filtro_etapa_id'],
            'filtro_equipo_id' => $filtro['filtro_equipo_id'],
            'filtro_user_id' => $filtro['filtro_user_id'],
            'filtro_sede_id' => $filtro['filtro_sede_id'],
            'filtro_fecha_desde' => $filtro['filtro_fecha_desde'],
            'filtro_fecha_hasta' => $filtro['filtro_fecha_hasta'],
            'paginate' => $filtro['paginate'],
        ])->links() }}
</x-sistema.tabla-contenedor>

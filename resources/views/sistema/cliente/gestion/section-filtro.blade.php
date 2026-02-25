<div class="w-full flex flex-col py-2 gap-4">

    <div class="w-full flex flex-col py-2 gap-4">
        <div id="contenedor_filtro_etapas" class="etapa-grid-container">
            <div class="etapa-grid">
                @foreach ($data_etapas as $etapaId => $etapaData)
                    <x-sistema.boton :type="$loop->last ? 'last' : 'middle'"
                        :title="$etapaData['nombre']"
                        :count="$etapaData['clientes_solo_count']"
                        :color="$etapaData['color']"
                        :active="request('filtro_etapa_id') == $etapaId" onclick="selectFiltroEtapa({{ $etapaId }})"
                        data-bs-toggle="tooltip"
                        data-bs-original-title="{{ $etapaData['tooltip'] ?? '' }}" />
                @endforeach
            </div>
        </div>

        <form action="{{ route('cliente-gestion.index') }}" method="GET" class="m-0">
            <div class="w-full flex justify-between">
                <div class="flex flex-col">
                    <div class="flex gap-1">
                        <div class="form-group">
                            <input type="hidden" name="filtro_etapa_id" id="filtro_etapa_id"
                                value="{{ request('filtro_etapa_id') ?? 0 }}">
                        </div>
                        @role(['sistema', 'gerente general', 'gerente comercial', 'asistente comercial', 'capacitador',
                            'planificacion'])
                            <div class="form-group flex flex-col">
                            @else
                                <div class="form-group flex flex-col" style="display: none;">
                                @endrole
                                <label for="filtro_sede_id" class="form-control-label">Sede:</label>
                                <select class="form-control" name="filtro_sede_id" id="filtro_sede_id"
                                    style="width: 250px;">
                                    <option></option>
                                    @foreach ($sedes as $item)
                                        <option value="{{ $item->id }}"
                                            @if ($item->id == request('filtro_sede_id')) selected @endif>{{ $item->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @role([
                                'sistema',
                                'gerente general',
                                'gerente comercial',
                                'asistente comercial',
                                'jefe comercial',
                                'capacitador',
                                'planificacion',
                                ])
                                <div class="form-group flex flex-col">
                                @else
                                    <div class="form-group flex flex-col" style="display: none;">
                                    @endrole
                                    <label for="filtro_equipo_id" class="form-control-label">Equipos:</label>
                                    <select class="form-control" name="filtro_equipo_id" id="filtro_equipo_id"
                                        style="width: 250px;">
                                        <option></option>
                                        @foreach ($equipos as $item)
                                            <option value="{{ $item->id }}"
                                                @if ($item->id == request('filtro_equipo_id')) selected @endif>{{ $item->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @role([
                                    'sistema',
                                    'gerente general',
                                    'gerente comercial',
                                    'asistente comercial',
                                    'jefe comercial',
                                    'supervisor',
                                    'capacitador',
                                    'planificacion',
                                    ])
                                    <div class="form-group flex flex-col">
                                    @else
                                        <div class="form-group flex flex-col" style="display: none;">
                                        @endrole
                                        <label for="filtro_user_id" class="form-control-label">Ejecutivo:</label>
                                        <select class="form-control" name="filtro_user_id" id="filtro_user_id"
                                            onchange="filtroAutomatico()" style="width: 250px;">
                                            <option></option>
                                            @foreach ($users as $item)
                                                <option value="{{ $item->id }}"
                                                    @if ($item->id == request('filtro_user_id')) selected @endif>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <div class="form-group">
                                        <label for="filtro_fecha_desde" class="form-control-label">Desde:</label>
                                        <input class="form-control" type="date"
                                            value="{{ request('filtro_fecha_desde') }}" id="filtro_fecha_desde"
                                            name="filtro_fecha_desde" onchange="filtroAutomatico()">
                                    </div>
                                    <div class="form-group">
                                        <label for="filtro_fecha_hasta" class="form-control-label">Hasta:</label>
                                        <input class="form-control" type="date"
                                            value="{{ request('filtro_fecha_hasta') }}" id="filtro_fecha_hasta"
                                            name="filtro_fecha_hasta" onchange="filtroAutomatico()">
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 items-center">
                                @role([
                                        'sistema',
                                        'gerente general',
                                        'gerente comercial',
                                        'administrador',
                                        'supervisor',
                                        'ejecutivo',
                                    ])
                                    <div class="flex gap-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-pink-600 text-base font-bold">Nuevos</span>
                                            <x-ui.count-rectangle>
                                                <x-slot:toggle>Clientes Nuevos</x-slot>
                                                {{ $countClienteNuevo }}
                                            </x-ui.count-rectangle>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-pink-600 text-base font-bold">Gestionados</span>
                                            <x-ui.count-rectangle>
                                                <x-slot:toggle>Clientes Gestionados</x-slot>
                                                {{ $countClienteGestionado }}
                                            </x-ui.count-rectangle>
                                        </div>
                                    </div>
                                @endrole
                                <div class="form-group">
                                    <label for="filtro_ruc" class="form-control-label">Ruc:</label>
                                    <input class="form-control" type="search" value="{{ request('filtro_ruc') }}"
                                        id="filtro_ruc" name="filtro_ruc" placeholder="Buscar por RUC o Razón Social">
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <div>
                                @can('sistema.gestion_cliente.asignar')
                                    <a href="javascript:;" class="btn bg-gradient-warning m-0" id="btnAssignClients"
                                        type="button">Asignar</a>
                                @endcan
                                @can('sistema.gestion_cliente.exportar')
                                    @if ($config['excel']['indotech'])
                                        <a href="javascript:;" class="btn bg-gradient-primary m-0" onclick="exportCliente()"
                                            type="button">Descargar</a>
                                        <a href="javascript:;" class="btn bg-gradient-primary m-0"
                                            onclick="exportFunnel('indotech')" type="button">Funnel Indotech</a>
                                    @endif
                                    @if ($config['excel']['secodi'])
                                        <a href="javascript:;" class="btn bg-gradient-primary m-0"
                                            onclick="exportFunnel('secodi')" type="button">Funnel Secodi</a>
                                    @endif
                                @endcan
                                {{-- Subir clientes --}}
                                @role([
                                        'sistema',
                                        'administrador',
                                    ])
                                    <a href="javascript:;"
                                        class="btn bg-gradient-warning m-0"
                                        id="btnSubirClientes"
                                        type="button">
                                        Subir Clientes
                                    </a>
                                @endrole
                            </div>
                            <div class="form-group">
                                <select class="form-control" name="paginate" id="paginate" style="width: 80px;"
                                    onchange="filtroAutomatico()">
                                    <option value="50" @if (50 == request('paginate')) selected @endif>50
                                    </option>
                                    <option value="100" @if (100 == request('paginate')) selected @endif>100
                                    </option>
                                    <option value="150" @if (150 == request('paginate')) selected @endif>150
                                    </option>
                                </select>
                            </div>
                        </div>
        </form>
    </div>
</div>
<script>
    function filtroAutomatico() {
        const form = document.querySelector('form[method="GET"]');
        if (form) {
            form.submit();
        }
    }

    // Actualizar fecha "hasta" automáticamente si está vacía
    document.getElementById('filtro_fecha_desde').addEventListener('change', function() {
        const fechaDesde = this.value; // Obtener la fecha seleccionada en "desde"
        const fechaHastaInput = document.getElementById('filtro_fecha_hasta');

        if (fechaDesde) {
            // Si la fecha "hasta" está vacía, establecerla como el día de hoy
            if (!fechaHastaInput.value) {
                const hoy = new Date().toISOString().split('T')[0];
                fechaHastaInput.value = hoy;
            }

            // Enviar el formulario automáticamente
            filtroAutomatico();
        }
    });

    // Manejar clics en los botones de etapa
    function selectFiltroEtapa(event, etapaId) {
        event.preventDefault();

        // Actualizar el campo oculto
        const filtroInput = document.getElementById('filtro_etapa_id');
        if (filtroInput) {
            filtroInput.value = etapaId;
        }

        // Enviar formulario
        const form = document.querySelector('form[method="GET"]');
        if (form) {
            form.submit();
        }
    }
    // Importar clientes
    document.getElementById('btnSubirClientes').addEventListener('click', function(e) {
        $.ajax({
            url: `{{ url('cliente-gestion/0/edit') }}`,
            method: "GET",
            data: {
                view: 'edit-importar',
            },
            success: function(result) {
                $('#contModal').html(result);
                openModal();
            },
            error: function(response) {
                console.log('error');
            }
        });
    });
</script>

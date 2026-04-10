@extends('layouts.app')

@can('sistema.dashboard')
    @section('content')
        <!-- Dependencias CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <div class="container-fluid p-1">
            @if($fechaSeleccionada || $equipoSeleccionado || $ejecutivoSeleccionado)
                <div class="mb-3 p-3" style="background: white; border: 1px solid #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <span class="text-muted me-2" style="font-weight: 500;">Filtros aplicados:</span>
                    @if($equipoSeleccionado)
                        <span class="badge rounded-pill me-2" style="background: #6c757d; color: white; padding: 8px 12px;">
                            <i class="fas fa-users me-1"></i>
                            {{ $equipos->find($equipoSeleccionado)->nombre }}
                        </span>
                    @endif
                    @if($ejecutivoSeleccionado)
                        <span class="badge rounded-pill me-2" style="background: #6c757d; color: white; padding: 8px 12px;">
                            <i class="fas fa-user-tie me-1"></i>
                            {{ $ejecutivos->find($ejecutivoSeleccionado)->name }}
                        </span>
                    @endif
                    @if($fechaSeleccionada)
                        <span class="badge rounded-pill me-2" style="background: #6c757d; color: white; padding: 8px 12px;">
                            <i class="fas fa-calendar-alt me-1"></i>
                            {{ $fechaSeleccionada->format('m/Y') }}
                        </span>
                    @endif
                </div>
            @endif

            <div class="row">
                <!-- Sección Filtros -->
                <div class="col-md-2 d-flex flex-column pe-0">
                    <div class="shadow-sm mb-3" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                        <div class="card-body p-3">
                            <h5 class="card-title mb-3 text-muted fw-medium">
                                <i class="fas fa-filter me-2"></i>Filtros
                            </h5>
                            <form method="GET" action="{{ route('dashboard') }}" id="autoSubmitForm">
                                <!-- Equipo -->
                                <div class="mb-2">
                                    <label class="form-label text-muted mb-1 fs-6">Equipo</label>
                                    <select name="equipo" id="equipo" class="form-select fs-6">
                                        <option value="">Todos</option>
                                        @foreach($equipos as $equipo)
                                            <option value="{{ $equipo->id }}" {{ $equipoSeleccionado == $equipo->id ? 'selected' : '' }}>
                                                {{ $equipo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- Ejecutivo -->
                                <div class="mb-2">
                                    <label class="form-label text-muted mb-1 fs-6">Ejecutivo</label>
                                    <select name="ejecutivo" id="ejecutivo" class="form-select fs-6">
                                        <option value="">Todos</option>
                                        @if($equipoSeleccionado)
                                            @foreach($ejecutivos as $ej)
                                                <option value="{{ $ej->id }}" {{ $ejecutivoSeleccionado == $ej->id ? 'selected' : '' }}>
                                                    {{ $ej->name }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled class="small">Seleccione un equipo</option>
                                        @endif
                                    </select>
                                </div>
                                
                                <!-- Selector de Fecha -->
                                <div class="mb-2">
                                    <label class="form-label text-muted mb-1 fs-6">Periodo</label>
                                    <div class="input-group">
                                        <input type="text" 
                                            class="form-control datepicker fs-6" 
                                            name="fecha"
                                            value="{{ $fechaSeleccionada ? $fechaSeleccionada->format('m/Y') : '' }}"
                                            placeholder="MM/AAAA"
                                            autocomplete="off">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-calendar-alt text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Limpiar filtros -->
                                <div class="text-center mt-2">
                                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary w-100">
                                        <i class="fas fa-times me-1"></i>Limpiar filtros
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Métricas -->
                    <div class="shadow-sm mb-3" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                        <div class="card-body p-3">
                            <h5 class="card-title mb-2 text-muted fw-medium">
                                <i class="fas fa-chart-line me-2"></i>Métricas
                            </h5>
                            
                            <div class="d-grid gap-2">
                                <!-- Total Clientes -->
                                <div class="metric-item p-2 bg-light rounded-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted mb-1 fs-6">Total Clientes</span>
                                        <strong class="text-dark">{{ $totalClientes }}</strong>
                                    </div>
                                </div>
                    
                                <!-- Etapa Cinco -->
                                <div class="metric-item p-2 bg-light rounded-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted mb-1 fs-6"">{{ $etapaCinco->nombre }}</span>
                                        <strong class="text-dark">{{ $clientesEnEtapaCinco }}</strong>
                                    </div>
                                </div>
                    
                                <!-- Convertibilidad -->
                                <div class="metric-item p-2 bg-light rounded-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted mb-1 fs-6"">Convertibilidad</span>
                                        <strong class="text-dark">{{ $convertibilidad }}%</strong>
                                    </div>
                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar bg-success" style="width: {{ $convertibilidad }}%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="col-md-10">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <div class="shadow-sm h-100" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                                <div class="card-body">
                                    <h5 class="card-title text-muted"><i class="fas fa-chart-pie me-1"></i> Distribución por Etapas</h5>
                                    <div class="mt-3">
                                        {!! $chart->container() !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3 d-flex flex-column gap-3">
                            <div class="shadow-sm flex-fill" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                                <div class="card-body">
                                    <h5 class="card-title text-muted"><i class="fas fa-percentage me-1"></i> Tasa de Conversión</h5>
                                    <div class="mt-3">
                                        {!! $conversionChart->container() !!}
                                    </div>
                                </div>
                            </div>
                            <div class="shadow-sm flex-fill" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                                <div class="card-body">
                                    <h5 class="card-title text-muted"><i class="fas fa-layer-group me-1"></i> Distribución Tipo Base</h5>
                                    <div class="mt-3">
                                        {!! $tipoBaseChart->container() !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Gestión Comercial - Pipeline de Ventas -->
        <div class="row mt-4">
            <div class="col-12 mb-3">
                <h5 class="text-muted fw-medium"><i class="fas fa-funnel-dollar me-2"></i>Gestión Comercial - Pipeline de Ventas</h5>
            </div>
        </div>
        <div class="row">
            <div class="col-md-7 mb-3">
                <div class="shadow-sm" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-chart-bar me-1"></i> Funnel de Conversión por Etapas</h5>
                        <div class="mt-3">
                            {!! $funnelChart->container() !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 mb-3">
                <div class="shadow-sm" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-tags me-1"></i> Distribución por Etiqueta</h5>
                        <div class="mt-3">
                            {!! $etiquetaChart->container() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 mb-3">
                <div class="shadow-sm" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-chart-line me-1"></i> Clientes Nuevos por Mes</h5>
                        <div class="mt-3">
                            {!! $clientesNuevosChart->container() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contactabilidad -->
        <div class="row mt-4">
            <div class="col-12 mb-3">
                <h5 class="text-muted fw-medium"><i class="fas fa-phone-alt me-2"></i>Contactabilidad</h5>
            </div>
        </div>
        <div class="row">
            <div class="col-md-5 mb-3">
                <div class="shadow-sm" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-chart-pie me-1"></i> Contactados vs No Contactados</h5>
                        <div class="mt-3">
                            {!! $contactadosChart->container() !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7 mb-3">
                <div class="shadow-sm" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-chart-bar me-1"></i> Canal de Contacto</h5>
                        <div class="mt-3">
                            {!! $canalesContactoChart->container() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 mb-3">
                <div class="shadow-sm" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-chart-line me-1"></i> Actividad de Contacto por Mes</h5>
                        <div class="mt-3">
                            {!! $actividadContactoChart->container() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gestiones Diarias -->
        <div class="row mt-4">
            <div class="col-12 mb-3">
                <h5 class="text-muted fw-medium"><i class="fas fa-clipboard-list me-2"></i>Gestiones Diarias — {{ $mesGestionesDiarias->translatedFormat('F Y') }}</h5>
            </div>
        </div>

        <!-- Fila 1: KPIs (izquierda) + Gestiones por Día (derecha) -->
        <div class="row mb-3">
            <div class="col-md-4 d-flex flex-column">
                <!-- Info cards: Equipo + Corte -->
                <div class="d-flex gap-2 mb-3">
                    @if($nombreEquipoGD)
                        <div class="shadow-sm flex-fill" style="background-color: #ffffff; padding: 10px 15px; border-radius: 10px; border: 1px solid #ddd;">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Equipo</span>
                            <strong style="font-size: 1rem; color: #333;">{{ $nombreEquipoGD }}</strong>
                        </div>
                    @endif
                    <div class="shadow-sm flex-fill" style="background-color: #ffffff; padding: 10px 15px; border-radius: 10px; border: 1px solid #ddd;">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Corte</span>
                        <strong style="font-size: 1rem; color: #333;">{{ $fechaCorte->format('d/m/Y H:i:s') }}</strong>
                    </div>
                </div>
                <!-- KPI: Total Gestiones -->
                <div class="shadow-sm mb-3" style="background-color: #ffffff; padding: 20px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-tasks" style="font-size: 2rem; color: #775DD0;"></i>
                        </div>
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.85rem;">Total Gestiones</span>
                            <strong style="font-size: 1.8rem; color: #333;">{{ number_format($totalGestionesMes, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
                <!-- KPI: Clientes Nuevos -->
                <div class="shadow-sm" style="background-color: #ffffff; padding: 20px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-user-plus" style="font-size: 2rem; color: #00E396;"></i>
                        </div>
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.85rem;">Clientes Nuevos</span>
                            <strong style="font-size: 1.8rem; color: #333;">{{ number_format($clientesNuevosMes, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="shadow-sm h-100" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-chart-line me-1"></i> Total Gestiones por Día</h5>
                        <div class="mt-3">
                            {!! $gestionesPorDiaChart->container() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 2: Tabla Gestiones diarias (izquierda) + Ranking (derecha) -->
        <div class="row mb-3">
            <div class="col-md-7">
                <div class="shadow-sm h-100" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-table me-1"></i> Gestiones diarias por Ejecutivo</h5>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-muted" style="position: sticky; left: 0; background: #f8f9fa; z-index: 1;">Ejecutivo</th>
                                        @foreach($diasConGestiones as $dia)
                                            <th class="text-center text-muted">{{ \Carbon\Carbon::parse($dia)->format('d') }}</th>
                                        @endforeach
                                        <th class="text-center text-muted fw-bold" style="background: #f0f0f0;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gestionesPivot as $ejecutivo => $dias)
                                        <tr>
                                            <td class="fw-medium text-nowrap" style="position: sticky; left: 0; background: white; z-index: 1;">{{ $ejecutivo }}</td>
                                            @php $totalEjecutivo = 0; @endphp
                                            @foreach($diasConGestiones as $dia)
                                                @php
                                                    $valor = $dias[$dia] ?? 0;
                                                    $totalEjecutivo += $valor;
                                                    $bgColor = $valor == 0 ? '' : ($valor >= 30 ? 'background: #7b2d8e; color: white;' : ($valor >= 15 ? 'background: #a855f7; color: white;' : 'background: #e9d5f5;'));
                                                @endphp
                                                <td class="text-center" style="{{ $bgColor }}">{{ $valor ?: '' }}</td>
                                            @endforeach
                                            <td class="text-center fw-bold" style="background: #f0f0f0;">{{ $totalEjecutivo }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="shadow-sm h-100" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-trophy me-1"></i> Ranking de Gestiones por Ejecutivo</h5>
                        <div class="mt-3">
                            {!! $rankingEjecutivosChart->container() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 3: Tabla Clientes nuevos por Ejecutivo (ancho completo) -->
        <div class="row">
            <div class="col-12 mb-3">
                <div class="shadow-sm" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-table me-1"></i> Clientes nuevos por Ejecutivo</h5>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-muted" style="position: sticky; left: 0; background: #f8f9fa; z-index: 1;">Ejecutivo</th>
                                        @foreach($diasConNuevos as $dia)
                                            <th class="text-center text-muted">{{ \Carbon\Carbon::parse($dia)->format('d') }}</th>
                                        @endforeach
                                        <th class="text-center text-muted fw-bold" style="background: #f0f0f0;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($nuevosPivot as $ejecutivo => $dias)
                                        <tr>
                                            <td class="fw-medium text-nowrap" style="position: sticky; left: 0; background: white; z-index: 1;">{{ $ejecutivo }}</td>
                                            @php $totalEjecutivo = 0; @endphp
                                            @foreach($diasConNuevos as $dia)
                                                @php
                                                    $valor = $dias[$dia] ?? 0;
                                                    $totalEjecutivo += $valor;
                                                    $bgColor = $valor == 0 ? '' : ($valor >= 15 ? 'background: #e67e22; color: white;' : ($valor >= 5 ? 'background: #f39c12; color: white;' : 'background: #fdebd0;'));
                                                @endphp
                                                <td class="text-center" style="{{ $bgColor }}">{{ $valor ?: '' }}</td>
                                            @endforeach
                                            <td class="text-center fw-bold" style="background: #f0f0f0;">{{ $totalEjecutivo }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 4: Mapa de Gestiones por Departamento -->
        <div class="row">
            <div class="col-12 mb-3">
                <div class="shadow-sm" style="background-color: #ffffff; padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
                    <div class="card-body">
                        <h5 class="card-title text-muted"><i class="fas fa-map-marked-alt me-1"></i> Clientes por Departamento</h5>
                        <div id="mapa-peru" style="height: 500px; border-radius: 8px; margin-top: 10px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scripts -->
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.es.min.js"></script>
        
        <script>
            // Inicializar Datepicker
            $('.datepicker').datepicker({
                language: 'es',
                format: 'mm/yyyy',
                startView: 'months',
                minViewMode: 'months',
                autoclose: true
            }).on('changeDate', function(e) {
                $('#autoSubmitForm').submit();
            });

            // Enviar el formulario al cambiar el ejecutivo
            document.getElementById('ejecutivo').addEventListener('change', function() {
                document.getElementById('autoSubmitForm').submit();
            });

            // Limpiar ejecutivo y enviar el formulario al cambiar el equipo
            document.getElementById('equipo').addEventListener('change', function() {
                document.getElementById('ejecutivo').value = ''; // Limpiar el ejecutivo
                document.getElementById('autoSubmitForm').submit(); // Enviar el formulario
            });
        </script>

        {{ $chart->script() }}
        {{ $conversionChart->script() }}
        {{ $tipoBaseChart->script() }}
        {{ $funnelChart->script() }}
        {{ $clientesNuevosChart->script() }}
        {{ $etiquetaChart->script() }}
        {{ $contactadosChart->script() }}
        {{ $canalesContactoChart->script() }}
        {{ $actividadContactoChart->script() }}
        {{ $gestionesPorDiaChart->script() }}
        {{ $rankingEjecutivosChart->script() }}

        <script>
        (function() {
            var datos = @json($clientesPorDepartamento);
            var maxGestiones = Math.max.apply(null, Object.values(datos).concat([1]));

            function getColor(gestiones) {
                if (!gestiones || gestiones === 0) return '#f0f0f0';
                var ratio = gestiones / maxGestiones;
                if (ratio >= 0.75) return '#4a1264';
                if (ratio >= 0.50) return '#7b2d8e';
                if (ratio >= 0.25) return '#a855f7';
                if (ratio >= 0.10) return '#c084fc';
                return '#e9d5f5';
            }

            var map = L.map('mapa-peru', {
                zoomControl: true,
                scrollWheelZoom: false
            }).setView([-9.19, -75.0], 5);

            fetch('{{ asset('geojson/peru_departamentos.geojson') }}')
                .then(function(r) { return r.json(); })
                .then(function(geojson) {
                    L.geoJSON(geojson, {
                        style: function(feature) {
                            var nombre = feature.properties.NOMBDEP;
                            var gestiones = datos[nombre] || 0;
                            return {
                                fillColor: getColor(gestiones),
                                weight: 1,
                                color: '#fff',
                                fillOpacity: 0.85
                            };
                        },
                        onEachFeature: function(feature, layer) {
                            var nombre = feature.properties.NOMBDEP;
                            var gestiones = datos[nombre] || 0;
                            layer.bindTooltip(
                                '<strong>' + nombre.charAt(0) + nombre.slice(1).toLowerCase() + '</strong><br>' +
                                gestiones.toLocaleString('es-PE') + ' clientes',
                                { sticky: true }
                            );
                            layer.on('mouseover', function() {
                                this.setStyle({ weight: 2, color: '#333', fillOpacity: 1 });
                            });
                            layer.on('mouseout', function() {
                                this.setStyle({ weight: 1, color: '#fff', fillOpacity: 0.85 });
                            });
                        }
                    }).addTo(map);

                    // Leyenda
                    var legend = L.control({ position: 'bottomright' });
                    legend.onAdd = function() {
                        var div = L.DomUtil.create('div', 'info legend');
                        div.style.cssText = 'background:white;padding:8px 12px;border-radius:8px;font-size:12px;line-height:1.8;box-shadow:0 2px 6px rgba(0,0,0,0.2)';
                        div.innerHTML =
                            '<strong style="display:block;margin-bottom:4px;">Clientes</strong>' +
                            '<span style="display:inline-block;width:12px;height:12px;background:#4a1264;margin-right:6px;border-radius:2px;"></span>Muy alto (75%+)<br>' +
                            '<span style="display:inline-block;width:12px;height:12px;background:#7b2d8e;margin-right:6px;border-radius:2px;"></span>Alto (50-74%)<br>' +
                            '<span style="display:inline-block;width:12px;height:12px;background:#a855f7;margin-right:6px;border-radius:2px;"></span>Medio (25-49%)<br>' +
                            '<span style="display:inline-block;width:12px;height:12px;background:#c084fc;margin-right:6px;border-radius:2px;"></span>Bajo (10-24%)<br>' +
                            '<span style="display:inline-block;width:12px;height:12px;background:#e9d5f5;margin-right:6px;border-radius:2px;"></span>Muy bajo (&lt;10%)<br>' +
                            '<span style="display:inline-block;width:12px;height:12px;background:#f0f0f0;margin-right:6px;border-radius:2px;"></span>Sin datos';
                        return div;
                    };
                    legend.addTo(map);
                });
        })();
        </script>

        <style>
            /* Asegúrate de que los gráficos tengan bordes suaves */
            .apexcharts-slice {
                stroke-linejoin: round; /* Bordes redondeados */
                stroke-width: 0; /* Elimina el borde */
            }

            /* Ajusta el contenedor del gráfico */
            .apexcharts-canvas {
                border-radius: 8px;
                overflow: hidden;
                margin: 0 auto;
            }

            .card {
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            
            .form-control:focus, .form-select:focus {
                border-color: #6c757d;
                box-shadow: 0 0 0 2px rgba(108,117,125,0.2);
            }
        </style>
    @endsection
@endcan
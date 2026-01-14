@extends('layouts.app')

@can('sistema.gestion_cliente')
    @section('content')
        <x-sistema.card-contenedor>
            <div class="p-3 pb-0">
                <div class="d-flex flex-column flex-md-row justify-content-between"> <!-- Cambia a columna en móvil -->
                    <div class="mb-2 mb-md-0"> <!-- Margen inferior solo en móvil -->
                        <x-sistema.titulo title="Gestión de Clientes" />
                        <x-ui.count-rectangle onclick="selectFiltroEtapa()" class="cursor-pointer">
                            <x-slot:toggle>General</x-slot>
                            {{ $count_total }}
                        </x-ui.count-rectangle>
                    </div>
                    <div>
                        @can('sistema.gestion_cliente.agregar')
                            <x-ui.button type="button" onclick="agregarCliente()">Agregar</x-ui.button>
                        @endcan
                    </div>
                </div>
                @include('sistema.cliente.gestion.section-filtro')
            </div>
            <div class="p-3" id="cont-tabla-cliente">
                @include('sistema.cliente.gestion.filtro')
            </div>
        </x-sistema.card-contenedor>
        @php
            $data_filtro = json_encode($filtro);
        @endphp
        <input type="hidden" name="data_filtro" id="data_filtro" value="{{ $data_filtro }}">
    @endsection
    @section('modal')
        <div id="contModal"></div>
    @endsection

    @section('script')
        <script>
            $('#filtro_sede_id').select2({
                placeholder: 'Seleccionar',
                allowClear: true,
            });
            $('#filtro_equipo_id').select2({
                placeholder: 'Seleccionar',
                allowClear: true,
            });
            $('#filtro_user_id').select2({
                placeholder: 'Seleccionar',
                allowClear: true,
            });
            $('#filtro_sede_id').on("change", function() {
                if ($(this).val()) {
                    $.ajax({
                        url: `{{ url('cliente-gestion/${$(this).val()}') }}`,
                        method: "GET",
                        data: {
                            view: 'show-select-sede'
                        },
                        success: function(data) {
                            let opt_equipo = '<option></option>';
                            let opt_user = '<option></option>';
                            data.equipos.map(function(item) {
                                opt_equipo += `<option value="${item.id}">${item.nombre}</option>`;
                            })
                            data.users.map(function(item) {
                                opt_user += `<option value="${item.id}">${item.name}</option>`;
                            })
                            $('#filtro_equipo_id').html(opt_equipo);
                            $('#filtro_user_id').html(opt_user);
                            filtroAutomatico();
                        },
                        error: function(response) {
                            console.log('error');
                        }
                    });
                } else {
                    filtroAutomatico();
                }
            });
            $('#filtro_equipo_id').on("change", function() {
                if ($(this).val()) {
                    $.ajax({
                        url: `{{ url('cliente-gestion/${$(this).val()}') }}`,
                        method: "GET",
                        data: {
                            view: 'show-select-equipo',
                            sede_id: $('#filtro_sede_id').val(),
                        },
                        success: function(data) {
                            let opt_user = '<option></option>';
                            data.users.map(function(item) {
                                opt_user += `<option value="${item.id}">${item.name}</option>`;
                            })
                            $('#filtro_user_id').html(opt_user);
                            filtroAutomatico();
                        },
                        error: function(response) {
                            console.log('error');
                        }
                    });
                } else {
                    $.ajax({
                        url: `{{ url('cliente-gestion/0') }}`,
                        method: "GET",
                        data: {
                            view: 'show-select-user',
                            sede_id: $('#filtro_sede_id').val(),
                        },
                        success: function(data) {
                            let opt_user = '<option></option>';
                            data.users.map(function(item) {
                                opt_user += `<option value="${item.id}">${item.name}</option>`;
                            })
                            $('#filtro_user_id').html(opt_user);
                            filtroAutomatico();
                        },
                        error: function(response) {
                            console.log('error');
                        }
                    });
                }
            });

            function filtroAutomatico() {
                let filtro_etapa_id = $('#filtro_etapa_id').val();
                let filtro_sede_id = $('#filtro_sede_id').val();
                let filtro_equipo_id = $('#filtro_equipo_id').val();
                let filtro_user_id = $('#filtro_user_id').val();
                let filtro_fecha_desde = $('#filtro_fecha_desde').val();
                let filtro_fecha_hasta = $('#filtro_fecha_hasta').val();
                let filtro_ruc = $('#filtro_ruc').val();
                let paginate = $('#paginate').val();
                window.location.href =
                    `/cliente-gestion?filtro_etapa_id=${filtro_etapa_id}&filtro_sede_id=${filtro_sede_id}&filtro_equipo_id=${filtro_equipo_id}&filtro_user_id=${filtro_user_id}&filtro_fecha_desde=${filtro_fecha_desde}&filtro_fecha_hasta=${filtro_fecha_hasta}&filtro_ruc=${filtro_ruc}&paginate=${paginate}`;
            }

            function detalleCliente(cliente_id) {
                $.ajax({
                    url: `{{ url('cliente-gestion/${cliente_id}/edit') }}`,
                    method: "GET",
                    data: {
                        view: 'edit-detalle'
                    },
                    success: function(result) {
                        $('#contModal').html(result);
                        openModal();
                    },
                    error: function(response) {
                        console.log('error');
                    }
                });
            }

            function closeFicha() {
                closeModal();
                location.reload();
            }

            function selectFiltroEtapa(etapa_id = 0) {
                $('#contenedor_filtro_etapas button').each(function() {
                    let btn_id = $(this).attr('id');
                    $('#' + btn_id).css("opacity", 1);
                })
                $('#etapa_' + etapa_id).css("opacity", 0.7);
                $('#filtro_etapa_id').val(etapa_id);
                filtroAutomatico();
            }
            let filtro_etapa_id = $('#filtro_etapa_id').val();
            $('#etapa_' + filtro_etapa_id).css({
                'color': '#fff',
                'zoom': '1.1',
                'font-size': '1.2rem'
            });

            function agregarCliente() {
                $.ajax({
                    url: `{{ url('cliente/create') }}`,
                    method: "GET",
                    data: {
                        view: 'create'
                    },
                    success: function(result) {
                        $('#contModal').html(result);
                        openModal();
                    },
                    error: function(response) {
                        console.log('error');
                    }
                });
            }
            /**
             * Seleccionar clientes
             * */
            const a = document.getElementById('selectAllClients');
            if (a) {
                document.getElementById('selectAllClients').addEventListener('change', function(e) {
                    let checkboxes = document.querySelectorAll('tbody .form-check-input');
                    checkboxes.forEach(function(checkbox) {
                        checkbox.checked = e.target.checked;
                    });
                });
            }
            const b = document.getElementById('btnAssignClients');
            if (b) {
                document.getElementById('btnAssignClients').addEventListener('click', function(e) {
                    let selectedClients = [];
                    let checkboxes = document.querySelectorAll('tbody .form-check-input:checked');
                    checkboxes.forEach(function(checkbox) {
                        selectedClients.push(checkbox.value);
                    });
                    if (selectedClients.length === 0) {
                        alert('Seleccione un cliente, ¡Por favor!');
                    } else {
                        $.ajax({
                            url: `{{ url('cliente-gestion/0/edit') }}`,
                            method: "GET",
                            data: {
                                view: 'edit-asignar',
                                clients: selectedClients,
                            },
                            success: function(result) {
                                $('#contModal').html(result);
                                openModal();
                            },
                            error: function(response) {
                                console.log('error');
                            }
                        });
                    }
                })
            }

            /**
             * Capturar clientes seleccionados para asignar a un ejecutivo
             * */

            function exportCliente() {
                let filtro = $('#data_filtro').val();
                window.location.href = "/clientes/export?filtro=" + filtro;
            }

            function exportFunnel(empresa) {
                let filtro = $('#data_filtro').val();

                // Navegación directa para descarga (más fiable para respuestas en streaming)
                const url = `/export/${empresa}/funnel?filtro=${encodeURIComponent(filtro)}`;
                window.location.href = url;
            }
        </script>
    @endsection
@endcan

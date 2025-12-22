@props([
    'data' => '',
    'onclickCloseModal' => 'closeModal()',
])
@php
    $cliente = $data['cliente'];
    $contactos = $data['contactos'];
    $sucursales = $data['sucursales'];
    $comentarios = $data['comentarios'];
    $movistar = $data['movistar'];
    $ventas = $data['ventas'];
    $notificacion = $data['notificacion'];
@endphp
<x-sistema.modal title="Detalle Cliente" dialog_id="dialog" :$onclickCloseModal style="width: 80vw;">
    <div style="display: none;">
        <input type="hidden" id="cliente_id" name="cliente_id" value="{{ $cliente->id }}">
    </div>
    <div class="row p-1 bg-gray-200">
        {{-- COLUMNA PRINCIPAL: 8 columnas --}}
        <div class="m-0 p-2 col-md-8">
            {{-- DATOS DEL CLIENTE --}}
            <div class="mb-0">
                <x-sistema.cliente.datos :$cliente>
                    @role(['ejecutivo', 'sistema'])
                        <x-slot:botonHeader>
                            <button class="btn btn-primary" id="btn-editar-cliente" onclick="editarCliente()">Editar</button>
                            <button class="btn btn-primary d-none" id="btn-guardar-cliente" onclick="guardarCliente()">Guardar</button>
                            <button class="btn btn-secondary d-none" id="btn-cancelar-cliente" onclick="cancelarCliente()">Cancelar</button>
                        </x-slot>
                    @endrole
                </x-sistema.cliente.datos>
            </div>
            {{-- DATOS ADICIONALES --}}
            <div class="p-2 mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <x-sistema.titulo title="Datos Adicionales" />
                    <button class="btn btn-sm btn-primary" onclick="toggleSeccion('panel-movistar', this)">
                        <i class="fa fa-chevron-up"></i>
                    </button>
                </div>
                <div id="panel-movistar" style="display: block;">
                    <x-sistema.cliente.movistars :$movistar>
                        @role(['ejecutivo'])
                            <x-slot:botonHeader>
                                <button class="btn btn-primary"
                                    id="btn-editar-datos"
                                    onclick="editarDatosAdicionales()">
                                    Editar
                                </button>
                                <button class="btn btn-primary d-none"
                                    id="btn-guardar-datos"
                                    onclick="guardarDatosAdicionales()">
                                    Guardar
                                </button>
                                <button class="btn btn-secondary d-none"
                                    id="btn-cancelar-datos"
                                    onclick="cancelarDatosAdicionales()">
                                    Cancelar
                                </button>
                            </x-slot>
                        @endrole
                    </x-sistema.cliente.movistars>
                </div>
            </div>
            {{-- CONTACTOS --}}
            <div class="p-2 mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <x-sistema.titulo title="Contactos" />
                    <button class="btn btn-sm btn-primary" onclick="toggleSeccion('panel-contactos', this)">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
                <div id="panel-contactos" style="display: none;">
                    <x-sistema.cliente.contactos :$contactos>
                        @role('ejecutivo')
                            <x-slot:botonFooter>
                                <button type="button"
                                    class="btn bg-primary text-white"
                                    onclick="saveContacto()"
                                    id="btn_guardar_contacto">
                                    Guardar
                                </button>
                                <button type="button" class="btn bg-secondary text-white"
                                    @click="resetFormContacto()">
                                    Cancelar
                                </button>
                            </x-slot>
                        @endrole
                    </x-sistema.cliente.contactos>
                </div>
            </div>
            {{-- SUCURSALES --}}
            <div class="p-2 mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <x-sistema.titulo title="Dirección de Instalación" />
                    <button class="btn btn-sm btn-primary" onclick="toggleSeccion('panel-sucursales', this)">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
                <div id="panel-sucursales" style="display: none;">
                    <x-sistema.cliente.sucursales :$sucursales>
                        @role('ejecutivo')
                            <x-slot:botonFooter>
                                <button type="button" class="btn bg-primary text-white"
                                    @click="saveSucursal()"
                                    id="btn_guardar_sucursal">
                                    Guardar
                                </button>
                                <button type="button" class="btn bg-secondary text-white"
                                    @click="resetForm()">
                                    Cancelar
                                </button>
                            </x-slot>
                        @endrole
                    </x-sistema.cliente.sucursales>
                </div>
            </div>
            {{-- PRODUCTOS EN NEGOCIACIÓN --}}
            <div class="p-2 mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <x-sistema.titulo title="Productos en Negociación" />
                    <button class="btn btn-sm btn-primary" onclick="toggleSeccion('panel-ventas', this)">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
                <div id="panel-ventas" style="display: none;">
                    <x-sistema.cliente.ventas />
                </div>
            </div>
            {{-- AGENDA --}}
            <div class="p-2 mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <x-sistema.titulo title="Agenda" />
                    <button class="btn btn-sm btn-primary" onclick="toggleSeccion('panel-agenda', this)">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
                <div id="panel-agenda" style="display: none;">
                    <x-sistema.notificacion.create :$notificacion>
                        @role('ejecutivo')
                            <x-slot:botonFooter>
                                <button type="button" class="btn bg-primary text-white"
                                    onclick="guardarNotificacion()">Agregar</button>
                            </x-slot>
                        @endrole
                    </x-sistema.notificacion.create>
                </div>
            </div>
        </div>

        {{-- COLUMNA LATERAL: 4 columnas --}}
        <div class="m-0 p-2 col-md-4">
            {{-- ETAPA --}}
            <div class="p-0 mb-1">
                <x-sistema.cliente.etapas>
                    @role('ejecutivo')
                        <x-slot:botonFooter>
                            <button type="button" class="btn bg-primary text-white" onclick="editEtapa()"
                                id="btn_editar_etapa">Editar</button>
                            <button type="button" class="btn bg-primary text-white" onclick="saveEtapa()"
                                id="btn_guardar_etapa" disabled>Guardar</button>
                        </x-slot>
                    @endrole
                </x-sistema.cliente.etapas>
            </div>
            {{-- COMENTARIOS --}}
            <div class="p-0 mb-1">
                <x-sistema.cliente.comentarios :$comentarios>
                    @role('ejecutivo')
                        <x-slot:botonFooter>
                            <button type="button" class="btn bg-primary text-white"
                                onclick="saveComentario()">Agregar</button>
                        </x-slot>
                    @endrole
                </x-sistema.cliente.comentarios>
            </div>
        </div>
    </div>
    {{-- BOTÓN CERRAR --}}
    <div class="text-end mt-3" style="background: gray-100;">
        <button type="button" class="btn btn-primary" onclick="{{ $onclickCloseModal }}">
            <i class="fa-solid fa-xmark me-1"></i> Cerrar
        </button>
    </div>
</x-sistema.modal>
<script>
    function toggleSeccion(id, button) {
        const panel = document.getElementById(id);
        const icon = button.querySelector('i');
        if (!panel || !icon) return;

        const isVisible = panel.style.display === 'block';
        panel.style.display = isVisible ? 'none' : 'block';
        icon.classList.toggle('fa-chevron-down', isVisible);
        icon.classList.toggle('fa-chevron-up', !isVisible);
    }

    function editCliente() {
        $(`#ruc, #razon_social, #ciudad, #btn_guardar_cliente, #generado_bot,
            #departamento_codigo, #provincia_codigo, #distrito_codigo`).prop('disabled', false)
    }

    function saveCliente() {
        let cliente_id = $('#cliente_id').val();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: `cliente-gestion/${cliente_id}`,
            method: "PUT",
            data: {
                view: 'update-cliente',
                ruc: $('#ruc').val(),
                razon_social: $('#razon_social').val(),
                ciudad: $('#ciudad').val(),
                departamento_codigo: $('#departamento_codigo').val(),
                provincia_codigo: $('#provincia_codigo').val(),
                distrito_codigo: $('#distrito_codigo').val(),
                generado_bot: $('#generado_bot').is(':checked'),
            },
            success: function(result) {
                $(`#ruc, #razon_social, #ciudad, #btn_guardar_cliente, #generado_bot,
                    #departamento_codigo, #provincia_codigo, #distrito_codigo`).prop('disabled', true);
            },
            error: function(response) {
                mostrarError(response);
            }
        });
    }
    function saveComentario() {
        const dialog = document.querySelector("#dialog");
        dialog.querySelectorAll('.is-invalid, .invalid-feedback').forEach(element => {
            element.classList.contains('is-invalid') ? element.classList.remove('is-invalid') : element
                .remove();
        });
        let cliente_id = $('#cliente_id').val();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: `cliente-gestion/${cliente_id}`,
            method: "PUT",
            data: {
                view: 'update-comentario',
                comentario: $('#comentario').val(),
                contactabilidad_id: $('input[name="contactabilidad_id"]:checked').val(),
            },
            success: function(result) {
                $('#comentario').val('');
                listComentario(result);
            },
            error: function(response) {
                mostrarError(response);
            }
        });
    }

    function listComentario(comentarios) {
        let html = "";
        comentarios.forEach(function(comentario) {
            html += `<div class="border rounded px-3 py-2 mb-2 bg-white shadow-sm" id="${comentario.id}">
                        <div class="mb-1 text-m text-orange-600 fw-semibold d-flex align-items-start">
                            <i class="fa-solid fa-comment text-orange-400 me-2 mt-1"></i>
                            <span>${comentario.comentario}</span>
                        </div>
                        <div class="text-xs text-muted d-flex flex-wrap gap-3 justify-end">
                            <span><i class="fa-solid fa-user me-1"></i>${comentario.usuario}</span>
                            <span><i class="fa-solid fa-tag me-1"></i>${comentario.etiqueta}</span>
                            <span><i class="fa-solid fa-info-circle me-1"></i>${comentario.contactabilidad}</span>
                            <span><i class="fa-solid fa-info-circle me-1"></i>${comentario.detalle}</span>
                            <span><i class="fa-solid fa-calendar-days me-1"></i>${comentario.fecha}</span>
                        </div>
                    </div>`;
        })
        $('#comentarios').html(html);
    }
    function editMovistar() {
        $('#form-datos-adicionales :input').prop('disabled', false);
    }
    selectEstadoWick({{ $data['cliente']->movistars->last()->estadowick_id ?? 0 }});

    function selectEstadoWick(estadowick_id) {
        $(`#estadowick_id option[value='${estadowick_id}']`).attr('selected', 'selected');
    }
    selectEstadoDito({{ $data['cliente']->movistars->last()->estadodito_id ?? 0 }});

    function selectEstadoDito(estadodito_id) {
        $(`#estadodito_id option[value='${estadodito_id}']`).attr('selected', 'selected');
    }
    selectClientetipo({{ $data['cliente']->movistars->last()->clientetipo_id ?? 0 }});

    function selectClientetipo(clientetipo_id) {
        $(`#clientetipo_id option[value='${clientetipo_id}']`).attr('selected', 'selected');
    }
    selectAgencia({{ $data['cliente']->movistars->last()->agencia_id ?? 0 }});

    function selectAgencia(agencia_id) {
        $(`#agencia_id option[value='${agencia_id}']`).attr('selected', 'selected');
    }

    function saveMovistar() {
        const dialog = document.querySelector("#dialog");
        dialog.querySelectorAll('.is-invalid, .invalid-feedback').forEach(element => {
            element.classList.contains('is-invalid') ? element.classList.remove('is-invalid') : element
                .remove();
        });
        let cliente_id = $('#cliente_id').val();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: `cliente-gestion/${cliente_id}`,
            method: "PUT",
            data: {
                view: 'update-movistar',
                // movisar
                estadowick_id: $('#estadowick_id').val() ?? 1,
                estadodito_id: $('#estadodito_id').val() ?? 1,
                linea_claro: $('#linea_claro').val() ?? '0',
                linea_entel: $('#linea_entel').val() ?? '0',
                linea_bitel: $('#linea_bitel').val() ?? '0',
                linea_movistar: $('#linea_movistar').val() ?? '0',
                clientetipo_id: $('#clientetipo_id').val() ?? 1,
                ejecutivo_salesforce: $('#ejecutivo_salesforce').val() ?? '',
                agencia_id: $('#agencia_id').val() ?? 1,
            },
            success: function(result) {
                $('#estadowick_id, #estadodito_id, #linea_claro, #linea_entel, #linea_bitel, #linea_movistar, #clientetipo_id, #ejecutivo_salesforce, #agencia_id, #btn_guardar_movistar')
                    .prop('disabled', true)
            },
            error: function(response) {
                mostrarError(response);
            }
        });
    }
    selectEtapa({{ $data['cliente']->etapas->last()->id }});

    function selectEtapa(etapa_id) {
        $(`#etapa_id option[value='${etapa_id}']`).attr('selected', 'selected');
        $('#etapa_id, #btn_guardar_etapa').prop('disabled', true)
    }

    function editEtapa() {
        $('#etapa_id, #btn_guardar_etapa').prop('disabled', false)
    }

    function saveEtapa() {
        const dialog = document.querySelector("#dialog");
        dialog.querySelectorAll('.is-invalid, .invalid-feedback').forEach(element => {
            element.classList.contains('is-invalid') ? element.classList.remove('is-invalid') : element
                .remove();
        });
        let cliente_id = $('#cliente_id').val();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: `cliente-gestion/${cliente_id}`,
            method: "PUT",
            data: {
                view: 'update-etapa',
                etapa_id: $("#etapa_id option:selected").val(),
                comentario: $('#comentario').val(),
                contactabilidad_id: $('input[name="contactabilidad_id"]:checked').val(),
            },
            success: function(result) {
                $('#comentario').val('');
                listComentario(result);
                $('#etapa_id, #btn_guardar_etapa').prop('disabled', true)
            },
            error: function(response) {
                mostrarError(response);
            }
        });
    }

    function editCargo() {
        $('#tbodyCargo tr').each(function() {
            $(this).find('#cargo_producto_nombre').prop('disabled', false);
            $(this).find('#cargo_cantidad').prop('disabled', false);
            $(this).find('#cargo_total').prop('disabled', false);
        });
        $('#btn_guardar_cargo').prop('disabled', false);
    }

    function saveCargo() {
        let cliente_id = $('#cliente_id').val();
        let dataCargo = [];
        $('#tbodyCargo tr').each(function() {
            dataCargo.push({
                producto_id: $(this).attr('id'),
                producto_nombre: $(this).find('#cargo_producto_nombre').val(),
                cantidad: $(this).find('#cargo_cantidad').val(),
                total: $(this).find('#cargo_total').val(),
            });
        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: `cliente-gestion/${cliente_id}`,
            method: "PUT",
            data: {
                view: 'update-cargo',
                dataCargo: dataCargo,
            },
            success: function(result) {
                $('#tbodyCargo tr').each(function() {
                    $(this).find('#cargo_producto_nombre').prop('disabled', true);
                    $(this).find('#cargo_cantidad').prop('disabled', true);
                    $(this).find('#cargo_total').prop('disabled', true);
                });
                $('#btn_guardar_cargo').prop('disabled', true);
            },
            error: function(response) {}
        });
    }

    function mostrarError(response) {
        let errors = response.responseJSON;
        if (errors) {
            let firstErrorKey = null;
            $.each(errors.errors, function(key, value) {
                $('#dialog #' + key).addClass('is-invalid');
                $('#dialog #' + key).after('<span class="invalid-feedback" role="alert"><strong>' + value +
                    '</strong></span>');
                if (!firstErrorKey) {
                    firstErrorKey = key;
                }
            });
            if (firstErrorKey) {
                $('#dialog #' + firstErrorKey).focus();
            }
        }
    }

    $(document).ready(function () {
        $('#form-datos-adicionales :input').prop('disabled', true);
    });
</script>

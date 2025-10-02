@props([
    'onclickCloseModal' => 'closeModal()',
])
<x-sistema.modal title="Agregar Cliente" dialog_id="dialog" :$onclickCloseModal style="width: 70vw;">
    <div class="row bg-gray-200">
        {{-- COLUMNA PRINCIPAL: 8 columnas --}}
        <div class="m-0 p-1 col-md-8">
            {{-- DATOS DEL CLIENTE --}}
            <div class="mb-1">
                <x-sistema.cliente.datos />
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
                    <x-sistema.cliente.movistars />
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
                    <x-sistema.cliente.contactos />
                </div>
            </div>
            {{-- SUCURSALES --}}
            <div class="p-2 mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <x-sistema.titulo title="Sucursales" />
                    <button class="btn btn-sm btn-primary" onclick="toggleSeccion('panel-sucursales', this)">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
                <div id="panel-sucursales" style="display: none;">
                    <x-sistema.cliente.sucursales />
                </div>
            </div>
            {{-- PRODUCTOS EN NEGOCIACIÓN --}}
            {{-- <div class="p-2 mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <x-sistema.titulo title="Productos en Negociación" />
                    <button class="btn btn-sm btn-primary" onclick="toggleSeccion('panel-ventas', this)">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
                <div id="panel-ventas" style="display: none;">
                    <x-sistema.cliente.ventas />
                </div>
            </div> --}}
        </div>
        {{-- COLUMNA LATERAL: 4 columnas --}}
        <div class="m-0 p-1 col-md-4">
            {{-- ETAPA --}}
            <div class="p-0 mb-1">
                <x-sistema.cliente.etapas />
            </div>
            {{-- COMENTARIOS --}}
            <div class="p-0 mb-1">
                <x-sistema.cliente.comentarios />
            </div>
        </div>
    </div>

    {{-- BOTÓN --}}
    <div class="text-end mt-3" style="background: gray-100;">
        <x-ui.button class="bg-slate-700" type="button" onclick="{{ $onclickCloseModal }}">Cancelar</x-ui.button>
        <x-ui.button type="button" onclick="submitCliente(this)">Agregar</x-ui.button>
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

    function submitCliente(button) {
        limpiarError();
        capturarToken();
        let dataCargo = [];
        $.each($('#producto_table tbody tr'), function(index, tr) {
            dataCargo.push({
                producto_id: $('# ' + tr.id).val(),
                producto_nombre: $('#producto_nombre' + tr.id).val(),
                detalle: $('#detalle' + tr.id).val(),
                cantidad: $('#cantidad' + tr.id).val(),
                precio: $('#precio' + tr.id).val(),
                total: $('#cargofijo' + tr.id).val(),
            });
        });
        $.ajax({
            url: `{{ url('cliente') }}`,
            method: "POST",
            data: {
                // cliente
                view: 'store',
                ruc: $('#ruc').val(),
                razon_social: $('#razon_social').val(),
                ciudad: $('#ciudad').val(),
                departamento_codigo: $('#departamento_codigo').val(),
                provincia_codigo: $('#provincia_codigo').val(),
                distrito_codigo: $('#distrito_codigo').val(),
                // contacto
                dni: $('#dni').val(),
                nombre: $('#nombre').val(),
                celular: $('#celular').val(),
                cargo: $('#cargo').val(),
                correo: $('#correo').val(),
                // sucursal
                sucursal_nombre: $('#sucursal_nombre').val(),
                sucursal_direccion: $('#sucursal_direccion').val(),
                sucursal_facilidad_tecnica: $('#sucursal_facilidad_tecnica').val(),
                sucursal_departamento_codigo: $('#sucursal_departamento_codigo').val(),
                sucursal_provincia_codigo: $('#sucursal_provincia_codigo').val(),
                sucursal_distrito_codigo: $('#sucursal_distrito_codigo').val(),
                // comentario
                contactabilidad: $('#contactabilidad').is(':checked'),
                comentario: $('#comentario').val(),
                // movistar
                estadowick_id: $('#estadowick_id').val() ?? null,
                estadodito_id: $('#estadodito_id').val() ?? 1,
                linea_claro: $('#linea_claro').val() ?? 0,
                linea_entel: $('#linea_entel').val() ?? 0,
                linea_bitel: $('#linea_bitel').val() ?? 0,
                linea_movistar: $('#linea_movistar').val() ?? 0,
                clientetipo_id: $('#clientetipo_id').val() ?? 1,
                ejecutivo_salesforce: $('#ejecutivo_salesforce').val() ?? '',
                agencia_id: $('#agencia_id').val() ?? 1,
                // etapa
                etapa_id: $('#etapa_id').val(),
                // cargo
                dataCargo: dataCargo,
            },
            beforeSend: function() {
                button.disabled = true;
            },
            success: function(response) {
                if (response.redirect) {
                    location.reload();
                } else {
                    alert('Posiblemente ya ha registrado el cliente, actualizar la página');
                }
            },
            error: function(response) {
                mostrarError(response)
                button.disabled = false;
            }
        });

    }
    // Funcion para limpiar el disabled de los inputs
    $(document).ready(function () {
        $('#form-datos-cliente :input').prop('disabled', false);
        $('#form-datos-adicionales :input').not('#linea_claro').prop('disabled', false);
    });
</script>

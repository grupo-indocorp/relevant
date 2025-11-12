@props([
    'botonHeader' => '',
    'botonFooter' => '',
    'movistar' => '',
])
<x-sistema.card class="p-4 m-2 mb-2 mx-0">
    <div class="d-flex flex-row flex-wrap justify-between items-center mb-2">
        <div></div>
        <div class="flex flex-row gap-2">
            {{ $botonHeader }}
        </div>
    </div>
    <div class="row" id="form-datos-adicionales">
        {{-- @if ($config['datosAdicionales']['lineaBitel']) @endif --}}
        <div class="col-md-4 mb-3">
            <label for="linea_claro">Líneas Claro</label>
            <input class="form-control"
                type="number"
                id="linea_claro"
                name="linea_claro"
                placeholder="0"
                value="{{ $movistar->linea_claro ?? '' }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="linea_movistar">Líneas Movistar</label>
            <input class="form-control"
                type="number"
                id="linea_movistar"
                name="linea_movistar"
                placeholder="0"
                value="{{ $movistar->linea_movistar ?? '' }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="linea_entel">Líneas Entel</label>
            <input class="form-control"
                type="number"
                id="linea_entel"
                name="linea_entel"
                placeholder="0"
                value="{{ $movistar->linea_entel ?? '' }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="linea_bitel">Líneas Bitel</label>
            <input class="form-control"
                type="number"
                id="linea_bitel"
                name="linea_bitel"
                placeholder="0"
                value="{{ $movistar->linea_bitel ?? '' }}">
        </div>
    </div>

    {{ $botonFooter }}
</x-sistema.card>

<script>
    let datosOriginales = {};

    function editarDatosAdicionales() {
        datosOriginales = obtenerValoresFormulario();
        $('#form-datos-adicionales :input').prop('disabled', false);
        $('#btn-editar-datos').addClass('d-none');
        $('#btn-guardar-datos, #btn-cancelar-datos').removeClass('d-none');
    }

    function cancelarDatosAdicionales() {
        establecerValoresFormulario(datosOriginales);
        $('#form-datos-adicionales :input').prop('disabled', true);
        $('#btn-editar-datos').removeClass('d-none');
        $('#btn-guardar-datos, #btn-cancelar-datos').addClass('d-none');
    }

    function obtenerValoresFormulario() {
        return {
            score: $('#score').val() ?? '0',
            cantidad_trabajador: $('#cantidad_trabajador').val() ?? '0',
            cantidad_sucursal: $('#cantidad_sucursal').val() ?? '0',
            linea_claro: $('#linea_claro').val() ?? '0',
            linea_entel: $('#linea_entel').val() ?? '0',
            linea_bitel: $('#linea_bitel').val() ?? '0',
            linea_movistar: $('#linea_movistar').val() ?? '0',
        };
    }

    function establecerValoresFormulario(data) {
        $('#score').val(data.score);
        $('#cantidad_trabajador').val(data.cantidad_trabajador);
        $('#cantidad_sucursal').val(data.cantidad_sucursal);
        $('#linea_claro').val(data.linea_claro);
        $('#linea_entel').val(data.linea_entel);
        $('#linea_bitel').val(data.linea_bitel);
        $('#linea_movistar').val(data.linea_movistar);
    }

    function guardarDatosAdicionales() {
        const data = obtenerValoresFormulario();

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
                ...data
            },
            success: function() {
                $('#form-datos-adicionales :input').prop('disabled', true);
                $('#btn-editar-datos').removeClass('d-none');
                $('#btn-guardar-datos, #btn-cancelar-datos').addClass('d-none');
            },
            error: function() {
                mostrarError(response);
            }
        });
    }
</script>

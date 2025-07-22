@props([
    'botonHeader' => '',
    'botonFooter' => '',
    'notificacion' => false,
])

<x-sistema.card class="p-4 m-2 mb-4 mx-0 bg-white shadow-md rounded-lg">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-between items-center mb-3">
        <div class="flex gap-2">
            {{ $botonHeader }}
        </div>
    </div>
    {{-- Formulario de nueva notificación --}}
    <div class="row gx-3 gy-2 align-items-end mb-3">
        <div class="col-md-6">
            <label for="notificaciontipo_id" class="form-label">Tipo de Agenda</label>
            <select class="form-select" id="notificaciontipo_id" name="notificaciontipo_id">
                @foreach ($notificaciontipos as $value)
                    <option value="{{ $value->id }}">{{ $value->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input class="form-control" type="date" value="{{ $fecha }}" id="fecha" name="fecha">
        </div>
        <div class="col-md-3">
            <label for="hora" class="form-label">Hora</label>
            <input class="form-control" type="time" id="hora" name="hora">
        </div>
    </div>

    <div class="mb-4">
        <textarea class="form-control" id="mensaje" name="mensaje" rows="3" placeholder="Escribe tu mensaje..."></textarea>
    </div>

    @if ($botonFooter)
        <div class="text-end mb-3">
            {{ $botonFooter }}
        </div>
    @endif

    {{-- Lista de Notificaciones --}}
    @if ($notificacion && count($notificacion))
        <div id="notificaciones-lista" class="border-t pt-1">
            @foreach ($notificacion as $index => $item)
                <div class="notificacion-item {{ $index >= 2 ? 'd-none notificacion-extra' : '' }} p-3 mb-1 border rounded shadow-sm bg-white"
                    id="notificacion-{{ $item->id }}">
                    {{-- Mensaje --}}
                    <div class="text-muted" style="font-size: 0.95rem; margin-top: 2px; white-space: pre-line;">
                        {{ trim($item->mensaje) }}
                    </div>
                    {{-- Cabecera: Asunto a la izquierda / Usuario + Fecha-Hora a la derecha --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold text-pink-600 fs-6">{{ $item->asunto }}</div>
                        <div class="d-flex gap-3 text-sm text-pink-600">
                            <div class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-user text-pink-500"></i>
                                <span>{{ $item->user->name }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-calendar-days text-pink-500"></i>
                                <span>
                                    {{ \Carbon\Carbon::parse($item->fecha)->format('d-m-Y') }}
                                    {{ \Carbon\Carbon::parse($item->hora)->format('h:i A') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            {{-- Botón Ver Más/Ver Menos --}}
            @if (count($notificacion) > 2)
                <div class="text-center mt-3">
                    <button class="btn btn-outline-secondary btn-sm" id="btn-toggle-notificaciones"
                        onclick="toggleNotificaciones()">
                        Ver más <i class="fa-solid fa-chevron-down"></i>
                    </button>
                </div>
            @endif
        </div>
    @endif
</x-sistema.card>

<script>
    let notificacionesExpandido = false;

    function toggleNotificaciones() {
        const extras = document.querySelectorAll('.notificacion-extra');
        const btn = document.getElementById('btn-toggle-notificaciones');

        extras.forEach(el => el.classList.toggle('d-none'));
        notificacionesExpandido = !notificacionesExpandido;

        btn.innerHTML = notificacionesExpandido
            ? 'Ver menos <i class="fa-solid fa-chevron-up"></i>'
            : 'Ver más <i class="fa-solid fa-chevron-down"></i>';
    }
    function guardarNotificacion() {
        const cliente_id = $('#cliente_id').val();
        const data = {
            notificaciontipo_id: $('#notificaciontipo_id').val(),
            fecha: $('#fecha').val(),
            hora: $('#hora').val(),
            mensaje: $('#mensaje').val(),
            cliente_id: cliente_id
        };
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: `notificacion`,
            method: "POST",
            data: {
                view: 'store_from_fichacliente',
                ...data
            },
            success: function(result) {
                $('#mensaje, #fecha, #hora').val('');
                actualizarListaNotificaciones(result);
            },
            error: function(response) {
                mostrarError(response);
            }
        });
    }
    function actualizarListaNotificaciones(notificacions) {
        const cliente_id = $('#cliente_id').val();
        let html = "";
        notificacions.forEach(function(notificacion) {
            html += `<div class="notificacion-item p-3 mb-1 border rounded shadow-sm bg-white"
                    id="notificacion-${notificacion.id}">
                    <div class="text-muted" style="font-size: 0.95rem; margin-top: 2px; white-space: pre-line;">
                        ${notificacion.mensaje}
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold text-pink-600 fs-6">${notificacion.asunto}</div>
                        <div class="d-flex gap-3 text-sm text-pink-600">
                            <div class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-user text-pink-500"></i>
                                <span>${notificacion.username}</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-calendar-days text-pink-500"></i>
                                <span>
                                    ${notificacion.fecha}
                                    ${notificacion.hora}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>`;
        })
        $('#notificaciones-lista').html(html);
        notificacionesExpandido = false;
    }
</script>


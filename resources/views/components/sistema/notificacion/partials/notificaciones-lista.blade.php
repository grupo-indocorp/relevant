<div id="notificaciones-lista" class="border-t pt-1">
    @foreach ($notificacion as $index => $item)
        <div class="notificacion-item {{ $index >= 2 ? 'd-none notificacion-extra' : '' }} p-3 mb-1 border rounded shadow-sm bg-white"
            id="notificacion-{{ $item->id }}">
            {{-- Mensaje --}}
            <div class="text-muted" style="font-size: 0.95rem; margin-top: 2px; white-space: pre-line;">
                {{ trim($item->mensaje) }}
            </div>

            {{-- Cabecera --}}
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

    @if (count($notificacion) > 2)
        <div class="text-center mt-3">
            <button class="btn btn-outline-secondary btn-sm" id="btn-toggle-notificaciones"
                onclick="toggleNotificaciones()">
                Ver más <i class="fa-solid fa-chevron-down"></i>
            </button>
        </div>
    @endif
</div>

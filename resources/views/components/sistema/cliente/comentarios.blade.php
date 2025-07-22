@props([
    'botonHeader' => '',
    'botonFooter' => '',
    'comentarios' => false,
])

<x-sistema.card class="p-4 m-2 mx-0">
    <!-- Encabezado -->
    <div class="d-flex flex-wrap justify-between align-items-center mb-3">
        <x-sistema.titulo title="Comentarios" />
        <div class="d-flex gap-2">
            {{ $botonHeader }}
        </div>
    </div>

    <!-- Área para nuevo comentario -->
    @role('ejecutivo')
        <div class="mb-3">
            <textarea class="form-control form-control-sm"
                id="comentario"
                name="comentario"
                rows="2"
                placeholder="Escribe tu comentario..."></textarea>
        </div>
    @endrole

    <!-- Botón pie -->
    <div class="text-end mb-3">
        {{ $botonFooter }}
    </div>

    <!-- Lista de Comentarios -->
    <div id="comentarios">
        @if ($comentarios && count($comentarios))
            @foreach ($comentarios as $comentario)
                <div class="border rounded px-3 py-2 mb-2 bg-white shadow-sm">
                    <!-- Texto del comentario con ícono -->
                    <div class="mb-1 text-m text-pink-600 fw-semibold d-flex align-items-start">
                        <i class="fa-solid fa-comment text-pink-400 me-2 mt-1"></i>
                        <span>{{ $comentario['comentario'] }}</span>
                    </div>

                    <!-- Detalles compactos -->
                    <div class="text-xs text-muted d-flex flex-wrap gap-3 justify-end">
                        <span><i class="fa-solid fa-user me-1"></i>{{ $comentario['usuario'] }}</span>
                        <span><i class="fa-solid fa-tag me-1"></i>{{ $comentario['etiqueta'] }}</span>
                        <span><i class="fa-solid fa-info-circle me-1"></i>{{ $comentario['detalle'] }}</span>
                        <span><i class="fa-solid fa-calendar-days me-1"></i>{{ $comentario['fecha'] }}</span>
                    </div>
                </div>
            @endforeach
        @else
            <div class="text-center text-gray-500 py-3 text-sm">
                <i class="fa-regular fa-comment-slash me-1"></i> No hay comentarios para mostrar.
            </div>
        @endif

    </div>
</x-sistema.card>

@extends('layouts.app')

@can('sistema.configuracion')
    @section('content')
        <x-sistema.card-contenedor>
            <div class="w-full py-2 px-4">
                <x-ui.link>
                    <x-slot:url>{{ url('configuracion') }}</x-slot>
                    <i class="fa-solid fa-arrow-left"></i> Configuración
                </x-ui.link>
            </div>
            <div class="w-full py-2 px-4 flex justify-between">
                <h3>Productos</h3>
                <x-ui.button onclick="agregarProducto()">
                    Agregar <i class="fa-solid fa-eyes"></i>
                </x-ui.button>
            </div>
            <div class="w-1/4">
                <x-sistema.card>
                    <div class="timeline timeline-one-side">
                        @foreach ($productos as $item)
                            <div class="timeline-block">
                                <span class="timeline-step text-pink-400">
                                    <i class="fa-solid fa-box-open-full"></i>
                                </span>
                                <div class="timeline-content mb-2">
                                    <h6 class="text-dark text-sm font-weight-bold m-0">{{ $item->nombre }}</h6>
                                    <button onclick="editarProducto({{ $item->id }})">Editar</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-sistema.card>
            </div>
        </x-sistema.card-contenedor>
    @endsection

    @section('modal')
        <div id="contModal"></div>
    @endsection

    @section('script')
        <script>
            function agregarProducto() {
                $.ajax({
                    url: `{{ url('configuracion-producto/create') }}`,
                    method: "GET",
                    data: {
                        view: 'create-producto'
                    },
                    success: function( result ) {
                        $('#contModal').html(result);
                        openModal();
                    },
                    error: function( response ) {
                        console.log('error');
                    }
                });
            }
            function editarProducto(producto_id) {
                $.ajax({
                    url: `{{ url('configuracion-producto/${producto_id}/edit') }}`,
                    method: "GET",
                    data: {
                        view: 'edit-producto'
                    },
                    success: function( result ) {
                        $('#contModal').html(result);
                        openModal();
                    },
                    error: function( response ) {
                        console.log('error');
                    }
                });
            }
        </script>
    @endsection
@endcan

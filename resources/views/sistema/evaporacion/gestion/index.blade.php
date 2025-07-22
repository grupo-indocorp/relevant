@extends('layouts.app')

@can('sistema.evaporacion-gestion')
    @section('content')
        <x-sistema.card-contenedor>
            <x-sistema.card-contenedor-header title="Gestión de Evaporación" />
            <div class="p-4">
                <x-ui.table id="table_notificacion">
                    <x-slot:thead>
                        <tr>
                            <th>{{ __("Tipo de Agenda") }}</th>
                            <th>{{ __("Cliente") }}</th>
                            <th>{{ __("Mensaje") }}</th>
                            <th>{{ __("Gestión") }}</th>
                            <th>{{ __("Estado") }}</th>
                            <th></th>
                        </tr>
                    </x-slot>
                    <x-slot:tbody>
                        @foreach ($notificacions as $value)
                            <tr>
                                <td>{{ $value->notificaciontipo->nombre }}</td>
                                <td>{{ $value->cliente->ruc ?? '' }}</td>
                                <td>{{ substr($value->mensaje, 0, 30) }}</td>
                                <td>{{ substr($value->comentario_gestion, 0, 50) }}</td>
                                <td>
                                    @if ($value->comentario_gestion_estado)
                                        <span class="bg-green-200 text-xs font-semibold font-se mb-0 px-3 py-1 rounded-lg">Confirmado</span>
                                    @else
                                        <span class="bg-pink-200 text-xs font-semibold font-se mb-0 px-3 py-1 rounded-lg">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="mx-3" data-bs-toggle="tooltip" data-bs-original-title="Detalle">
                                        <a href="javascript:;" class="cursor-pointer" onclick="gestionEvaporacionDetalle({{ $value->id }})">
                                            <i class="fa-solid fa-eyes"></i>
                                        </a>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </x-slot>
                    <x-slot:tfoot></x-slot>
                </x-ui.table>
                {{ $notificacions->links() }}
            </div>
        </x-sistema.card-contenedor>
    @endsection
    @section('script')
        <script>
            function gestionEvaporacionDetalle(notificacion_id) {
                $.ajax({
                    url: `{{ url('evaporacion-gestion/${notificacion_id}/edit') }}`,
                    method: "GET",
                    data: {
                        view: 'detalle',
                    },
                    success: function( result ) {
                        $('#contenedorModal').html(result);
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

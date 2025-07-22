<header class="container-fluid p-0">
    <div class="mb-2 p-3 rounded-3xl  shadow-md" style="background-color: #f1f1f1;">
        <nav class="flex justify-between items-center">
            <!-- Mensaje de bienvenida y frase motivadora -->
            <div>
                <h4 class="font-bold text-1xl text-[#5a009a] mb-2">{{ $saludo }}</h4>
                <h4 class="text-xl text-[#5a009ada] " id="frase-motivadora">{{ $fraseAleatoria }}</h4>
            </div>

            <!-- Botones de notificaciones y perfil -->
            <div class="flex items-center space-x-4">
                <!-- Botón de notificaciones -->
                @can('sistema.notificacion')
                <a href="{{ url('notificacion') }}" 
                    class="cursor-pointer p-2 rounded-full transition-all duration-200 hover:bg-[#5a009a]/25"
                    data-bs-toggle="tooltip" 
                    data-bs-placement="right"
                    data-bs-original-title="Agenda">
                    <i class="fa-solid fa-regular fa-calendar-days text-2xl text-[#5a009a] group-hover:scale-110 transition-transform duration-200"></i>
                </a>
                @endcan
                <x-ui.button type="button"
                    class="text-xs bg-[#5a009a] text-white px-3 py-2 rounded-lg hover:bg-[#5a009ada] transition duration-300"
                    onclick="cargarNotificacion('pendiente')">
                    {{ count(Helpers::NotificacionRecordatorio($user)) }} <i class="fa-solid fa-bell"></i>
                </x-ui.button>

                <!-- Menú de perfil -->
                <div class="relative ml-3" x-data="{ open: false }">
                    <div class="w-[2.4rem]">
                        <button x-on:click="open = true" type="button"
                            class="flex max-w-xs items-center rounded-full bg-[#5a009a] text-sm focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#5a009a]"
                            id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                            <img class="h-100 w-100 rounded-full" src="{{ $user->profile_photo_url }}"
                                alt="">
                        </button>
                    </div>

                    <div x-show="open" x-on:click.away="open=false"
                        class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-[#5a009ada] ring-opacity-5 focus:outline-none"
                        role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                        <a href="{{ route('profile.show') }}"
                            class="block text-[#5a009a] hover:text-[#5a009ada] rounded-md px-3 py-2 text-sm font-medium"
                            role="menuitem" tabindex="-1" id="user-menu-item-0">Perfil</a>
                        <a href="{{ url('cliente-gestion') }}"
                            class="block text-[#5a009a] hover:text-[#5a009ada] rounded-md px-3 py-2 text-sm font-medium"
                            role="menuitem" tabindex="-1" id="user-menu-item-1">Gestión de Clientes</a>
                        <form method="POST" action="{{ route('logout') }}" x-data>
                            @csrf
                            <a href="{{ route('logout') }}"
                                class="block text-[#5a009a] hover:text-[#5a009ada] rounded-md px-3 py-2 text-sm font-medium"
                                role="menuitem" tabindex="-1" id="user-menu-item-2"
                                @click.prevent="$root.submit();">Salir</a>
                        </form>
                    </div>

                </div>
            </div>
        </nav>
    </div>
</header>

<!-- Modal -->
@section('modal')
    <div id="contModal"></div>
@endsection

<!-- Scripts -->
<script>
    // Rotación de frases motivadoras
    const frases = @json($frases);
    const elementoFrase = document.getElementById('frase-motivadora');

    function cambiarFrase() {
        const fraseAleatoria = frases[Math.floor(Math.random() * frases.length)];
        elementoFrase.textContent = fraseAleatoria;
    }

    // Función para cargar notificaciones
    function cargarNotificacion(view) {
        $('#contModal').html('<p>Cargando...</p>');
        $.ajax({
            url: `{{ url('notificacion/create') }}`,
            method: "GET",
            data: {
                view: view
            },
            success: function(result) {
                $('#contModal').html(result);
                openModal();
            },
            error: function(response) {
                $('#contModal').html('<p>Error al cargar. Intenta de nuevo.</p>');
            }
        });
    }

    // Función para abrir el modal
    function openModal() {
        // Aquí puedes implementar la lógica para abrir el modal
        console.log('Modal abierto');
    }

    let ultimaPosicionScroll = window.pageYOffset;

    window.onscroll = function() {
        const posicionActual = window.pageYOffset;

        // Ocultar el header al hacer scroll hacia abajo
        if (ultimaPosicionScroll < posicionActual) {
            document.querySelector('header').classList.add('oculto');
        }
        // Mostrar el header al hacer scroll hacia arriba
        else {
            document.querySelector('header').classList.remove('oculto');
        }

        // Actualizar la última posición del scroll
        ultimaPosicionScroll = posicionActual;
    };
</script>

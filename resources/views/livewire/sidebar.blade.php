<aside class="h-screen"> <!-- Ancho reducido -->
    <!-- Fondo ultra minimalista con sutil acento naranja -->
    <div class="bg-white/25 backdrop-blur-md min-h-full p-1 border-r border-[#5a009a]/10">
        <ul class="p-0 m-0 flex flex-col items-center space-y-6"> <!-- Centrado vertical -->
            @if (is_array($links) || is_object($links))
                @foreach ($links as $link)
                    @can($link['can'])
                        <li class="group w-full flex justify-center">
                            <a 
                                href="{{ url($link['url']) }}" 
                                class="cursor-pointer p-2 rounded-full transition-all duration-200 hover:bg-[#5a009a]/25"
                                data-bs-toggle="tooltip" 
                                data-bs-placement="right"
                                data-bs-original-title="{{ $link['nombre'] }}"
                            >
                                <!-- Icono naranja puro con hover sutil -->
                                <i class="fa-solid {{ $link['icon'] }} text-2xl text-[#5a009a] group-hover:scale-110 transition-transform duration-200"></i>
                            </a>
                        </li>
                    @endcan
                @endforeach
            @endif

            <!-- Enlaces del sistema de búsqueda -->
            @if (isset($busquedaLinks) && count($busquedaLinks) > 0)
                @foreach ($busquedaLinks as $link)
                    @can($link['can'])
                        <li class="group w-full flex justify-center">
                            <a 
                                href="{{ url($link['url']) }}" 
                                class="cursor-pointer p-2 rounded-full transition-all duration-200 hover:bg-[#5a009a]/25"
                                data-bs-toggle="tooltip" 
                                data-bs-placement="right"
                                data-bs-original-title="{{ $link['nombre'] }}"
                            >
                                <!-- Icono azul para diferenciar el módulo de búsqueda -->
                                <i class="fa-solid {{ $link['icon'] }} text-2xl text-blue-600 group-hover:scale-110 transition-transform duration-200"></i>
                            </a>
                        </li>
                    @endcan
                @endforeach
            @endif
        </ul>
    </div>
</aside>
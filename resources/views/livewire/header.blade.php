<header class="flex-initial">
    <nav class="flex w-full justify-between">
        <div>
            <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                <li class="breadcrumb-item text-sm opacity-5 text-dark">{{ $user }}</li>
                <li class="breadcrumb-item text-sm text-dark active text-capitalize" aria-current="page">{{ str_replace('-', ' ', Request::path()) }}</li>
            </ol>
            <h6 class="font-weight-bolder mb-0 text-capitalize">{{ str_replace('-', ' ', Request::path()) }}</h6>
        </div>
        <div class="">
            <div class="hidden md:block">
                @auth
                    <div class="ml-4 flex items-center md:ml-6">
                    <button type="button" class="flex max-w-xs items-center rounded-full bg-pink-600 p-1 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-orange-950">
                        <span class="sr-only">Ver notificaciones</span>
                        <div class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <div class="fa-solid fa-bell"></div>
                        </div>
                    </button>
        
                    <!-- Imagen de perfil - Profile dropdown -->
                    <div class="relative ml-3" x-data="{ open:false}">
                        <div>
                        <button x-on:click="open = true" type="button" class="flex max-w-xs items-center rounded-full bg-pink-600 text-sm focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-orange-950" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                            <span class="sr-only">Open user menu</span>
                            <img class="h-8 w-8 rounded-full" src="{{auth()->user()->profile_photo_url}}" alt="">
                        </button>
                        </div>
        
                        <div x-show="open" x-on:click.away="open=false" class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                        <!-- Active: "bg-gray-100", Not Active: "" -->
                        <a href="{{route('profile.show')}}" class="block text-pink-950 hover:text-blue-400 rounded-md px-3 py-2 text-sm font-medium" role="menuitem" tabindex="-1" id="user-menu-item-0">Perfil</a>
                        <a href="{{ url('gestion_cliente') }}" class="block text-pink-950 hover:text-blue-400 rounded-md px-3 py-2 text-sm font-medium" role="menuitem" tabindex="-1" id="user-menu-item-1">Funnel</a>
        
                        <form method="POST" action="{{ route('logout') }}" x-data>
                            @csrf
        
                            <a href="{{ route('logout') }}" class="block text-pink-950 hover:text-blue-400 rounded-md px-3 py-2 text-sm font-medium" role="menuitem" tabindex="-1" id="user-menu-item-2" @click.prevent="$root.submit();">
                            Salir 
                            </a>
                        </form>
        
                        </div>
                    </div>
                    </div>
                @else
                <a href="{{route('login')}}" class="text-pink-950 hover:bg-pink-400 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Ingresar</a>
                <!--<a href="{{route('register')}}" class="text-blue-950 hover:bg-blue-400 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Registro</a>-->
                @endauth
            </div>
        </div>
    </nav>
</header>

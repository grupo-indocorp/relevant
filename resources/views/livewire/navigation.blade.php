<div>
  <div class="min-h-full" x-data="{open: false}">
    <nav class="bg-transparent">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
          <div class="flex items-center">

            <!-- Logotipo -->
            <a href="{{ url('/') }}" class="flex-shrink-0">
              @php $sistema = App\Models\Sistema::first(); @endphp
              @if (isset($sistema))
              <img class="h-8 w-8" src="{{ Storage::url($sistema->logo) }}" alt="INDOTECH S.A.C.">
              @else
              <img class="h-8 w-8" src="{{ asset('img/logo.png')}}" alt="INDOTECH S.A.C.">
              @endif
            </a>

            <!-- Menus Principal -->
            <div class="hidden md:block">
              <div class="ml-10 flex items-baseline space-x-4">
                <!-- Current: "bg-gray-900 text-white", Default: "text-gray-300 hover:bg-gray-700 hover:text-white" -->
                <a href="{{ url('/') }}" class="text-pink-950 hover:bg-pink-400 hover:text-white rounded-md px-3 py-2 text-sm font-medium" aria-current="page">Inicio</a>
                <a href="#" class="text-pink-950 hover:bg-pink-400 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Productos</a>
                <a href="#" class="text-pink-950 hover:bg-pink-400 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Promociones</a>
                <a href="#" class="text-pink-950 hover:bg-pink-400 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Nosotros</a>
                <a href="#" class="text-pink-950 hover:bg-pink-400 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Contactos</a>
              </div>
            </div>
          </div>

          <!-- imagen de perfil y notificaciones-->

          <div class="hidden md:block">
            @auth
            <div class="ml-4 flex items-center md:ml-6">
              <button type="button" class="rounded-full bg-pink-950 p-1 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-orange-950">
                <span class="sr-only">View notifications</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
              </button>

              <!-- Imagen de perfil - Profile dropdown -->
              <div class="relative ml-3" x-data="{ open:false}">
                <div>
                  <button x-on:click="open = true" type="button" class="flex max-w-xs items-center rounded-full bg-pink-950 text-sm focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-orange-950" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                    <span class="sr-only">Open user menu</span>
                    <img class="h-8 w-8 rounded-full" src="{{auth()->user()->profile_photo_url}}" alt="">
                  </button>
                </div>

                <!--
                  Dropdown menu, show/hide based on menu state.

                  Entering: "transition ease-out duration-100"
                    From: "transform opacity-0 scale-95"
                    To: "transform opacity-100 scale-100"
                  Leaving: "transition ease-in duration-75"
                    From: "transform opacity-100 scale-100"
                    To: "transform opacity-0 scale-95"
                -->
                <div x-show="open" x-on:click.away="open=false" class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                  <!-- Active: "bg-gray-100", Not Active: "" -->
                  <a href="{{route('profile.show')}}" class="block text-pink-950 hover:text-pink-400 rounded-md px-3 py-2 text-sm font-medium" role="menuitem" tabindex="-1" id="user-menu-item-0">Perfil</a>
                  <a href="{{ url('cliente-gestion') }}" class="block text-pink-950 hover:text-pink-400 rounded-md px-3 py-2 text-sm font-medium" role="menuitem" tabindex="-1" id="user-menu-item-1">Funnel</a>

                  <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf

                    <a href="{{ route('logout') }}" class="block text-pink-950 hover:text-pink-400 rounded-md px-3 py-2 text-sm font-medium" role="menuitem" tabindex="-1" id="user-menu-item-2" @click.prevent="$root.submit();">
                      Salir 
                    </a>
                  </form>

                </div>
              </div>
            </div>
            @else
            <a href="{{route('login')}}" class="text-pink-950 hover:bg-pink-400 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Ingresar</a>
            <!--<a href="{{route('register')}}" class="text-pink-950 hover:bg-pink-400 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Registro</a>-->
            @endauth
          </div>


          <div class="-mr-2 flex md:hidden">
            <!-- Mobile menu button -->
            <button x-on:click="open = true" type="button" class="inline-flex items-center justify-center rounded-md bg-pink-950 p-2 text-gray-400 hover:bg-gray-400 hover:text-pink-950 focus:outline-none focus:ring-2 focus:ring-orange-950 focus:ring-offset-2 focus:ring-offset-orange-950" aria-controls="mobile-menu" aria-expanded="false">
              <span class="sr-only">Open main menu</span>
              <!-- Menu open: "hidden", Menu closed: "block" -->
              <svg class="block h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
              </svg>
              <!-- Menu open: "block", Menu closed: "hidden" -->
              <svg class="hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile menu, show/hide based on menu state. -->
      <div class="md:hidden" id="mobile-menu" x-show="open" x-on:click.away="open=false">

        <!-- menu de opciones // movil-->
        <div class="space-y-1 px-2 pb-3 pt-2 sm:px-3">
          <!-- Current: "bg-gray-900 text-white", Default: "text-gray-300 hover:bg-gray-700 hover:text-white" -->
          <a href="#" class="bg-pink-950 text-white block rounded-md px-3 py-2 text-base font-medium" aria-current="page">Inicio</a>
          <a href="#" class="text-pink-950 hover:bg-pink-400 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Productos</a>
          <a href="#" class="text-pink-950 hover:bg-pink-400 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Promociones</a>
          <a href="#" class="text-pink-950 hover:bg-pink-400 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Nosotros</a>
          <a href="#" class="text-pink-950 hover:bg-pink-400 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Contactos</a>
        </div>


        <!-- imagen de perfil y notificaciones // movil-->
        @auth
        <div class="border-t border-pink-950 pb-3 pt-4">
          <div class="flex items-center px-5">
            <div class="flex-shrink-0">
              <img class="h-10 w-10 rounded-full" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
            </div>

            <div class="ml-3">
              <div class="text-base font-medium leading-none text-pink-700">Tom Cook</div>
              <div class="text-sm font-medium leading-none text-pink-900">tom@example.com</div>
            </div>

            <button x-on:click.away="open=false" type="button" class="ml-auto flex-shrink-0 rounded-full bg-pink-950 p-1 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-orange-950">
              <span class="sr-only">View notifications</span>
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
              </svg>
            </button>
          </div>
          <div x-show="open" class="mt-3 space-y-1 px-2">
            <a href="{{route('profile.show')}}" class="block rounded-md px-3 py-2 text-base font-medium text-pink-950 hover:bg-pink-400 hover:text-white">Perfil</a>
            <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-pink-950 hover:bg-pink-400 hover:text-white">Funell</a>
            <form method="POST" action="{{ route('logout') }}" x-data>
              @csrf
              <a href="{{ route('logout') }}" class="block rounded-md px-3 py-2 text-base font-medium text-pink-950 hover:bg-pink-400 hover:text-white" @click.prevent="$root.submit();">
                Salir
              </a>
            </form>

          </div>
        </div>
        @else
        <div class="border-t border-pink-950 pb-3 pt-0">
          <div x-show="open" class="mt-3 space-y-1 px-2">
            <a href="{{route('login')}}" class="text-pink-950 hover:bg-pink-400 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Ingresar</a>
            <!--<a href="{{route('register')}}" class="text-pink-950 hover:bg-pink-400 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Registro</a>-->
          </div>
        </div>
        @endauth


      </div>
    </nav>
  </div>
</div>
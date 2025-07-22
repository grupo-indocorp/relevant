@props([
    'title' => '',
    'dialog_id' => 'dialog',
    'onclickCloseModal' => 'closeModal()',
])

<dialog {{ $attributes->merge(['id' => $dialog_id, 'class' => 'w-[98vw] h-[95vh] max-w-none max-h-none p-0 overflow-hidden rounded-xl shadow-xl transition-all duration-300']) }}>
    <div class="flex flex-col h-full w-full bg-white ring-1 ring-gray-300 rounded-xl overflow-hidden">

        {{-- HEADER --}}
        <div class="flex items-center justify-between px-4 py-3 border-b bg-gradient-to-r from-gray-100 to-gray-100 shadow-sm">
            <h5 class="text-xl font-semibold text-pink-600 uppercase tracking-wide">
                {{ $title }}
            </h5>
            <button class="text-pink-500 hover:text-pink-600 transition-all duration-200 text-2xl"
                onclick="{{ $onclickCloseModal }}" title="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- CONTENIDO --}}
        <div class="flex-1 overflow-y-auto p-4 bg-gray-200">
            {{ $slot }}
        </div>

        {{-- FOOTER OPCIONAL --}}
        {{-- <div class="px-6 py-3 border-t text-end bg-white">
            <button class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                onclick="{{ $onclickCloseModal }}">Cerrar</button>
        </div> --}}
        
    </div>
</dialog>

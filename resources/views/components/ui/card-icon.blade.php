<section {{ $attributes->merge(['class' => 'w-full bg-slate-50 rounded-2xl flex-auto p-4 text-center hover:bg-slate-100']) }}>
    <h3 class="text-7xl text-transparent bg-gradient-to-r from-blue-400 to-#5a009a bg-clip-text">
        @if (isset($icon))
            {{ $icon }}
        @else
            <i class="fa-solid fa-bug"></i>
        @endif
    </h3>
    <h6 class="mb-0 font-bold text-slate-500">{{ $title }}</h6>
    <p class="mb-0 text-slate-400 text-sm leading-normal opacity-80">{{ $subtitle}}</p>
    {{ $slot }}
</section>

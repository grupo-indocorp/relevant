<a href="{{ $url }}" {{ $attributes->merge(['class' => 'bg-slate-100 text-slate-100 hover:text-pink-400 rounded-xl font-bold px-4 py-2']) }}>
    {{ $slot }}
</a>
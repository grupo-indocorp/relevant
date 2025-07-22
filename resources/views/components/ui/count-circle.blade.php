<section {{ $attributes->merge(['class' => 'w-[40px] h-[40px] bg-white text-pink-400 text-xl font-extrabold border-2 border-pink-500 flex justify-center items-center rounded-full']) }} data-bs-toggle="tooltip" data-bs-original-title="{{ $toggle }}">
    {{ $slot }}
</section>
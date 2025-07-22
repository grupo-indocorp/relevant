<section {{ $attributes->merge(['class' => 'w-[35px] h-[25px] bg-pink-200 text-pink-600 text-base font-extrabold border-2 border-pink-200 flex justify-center items-center rounded-lg']) }} data-bs-toggle="tooltip" data-bs-original-title="{{ $toggle }}">
    {{ $slot }}
</section>

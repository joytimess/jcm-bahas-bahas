{{-- Input password dengan tombol lihat/sembunyikan. Atribut diteruskan ke <input>. --}}
<div x-data="{ show: false }" class="relative">
    <x-text-input x-bind:type="show ? 'text' : 'password'" type="password" {{ $attributes->merge(['class' => 'block w-full pe-12']) }} />

    <button type="button"
            x-on:click="show = ! show"
            x-bind:aria-pressed="show.toString()"
            x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
            aria-label="Tampilkan kata sandi"
            class="absolute inset-y-0 end-0 flex w-11 items-center justify-center rounded-r-xl text-muted hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
        {{-- Mata terbuka: kata sandi tersembunyi --}}
        <svg x-show="! show" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
        {{-- Mata dicoret: kata sandi terlihat --}}
        <svg x-show="show" x-cloak viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.700 5.100A10 10 0 0 1 12 5c6.400 0 10 7 10 7a17 17 0 0 1-3.200 4.100M6.600 6.600A17 17 0 0 0 2 12s3.600 7 10 7a9.700 9.700 0 0 0 5.400-1.600"/><path d="M9.900 9.900a3 3 0 0 0 4.200 4.200"/><path d="m2 2 20 20"/></svg>
    </button>
</div>

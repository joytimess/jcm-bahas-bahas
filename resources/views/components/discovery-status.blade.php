<div aria-live="polite" class="space-y-3 text-center">
    <a x-show="sessionExpired && !loadError" x-cloak href="{{ route('login') }}" class="inline-flex min-h-[44px] items-center text-primary underline">Masuk kembali</a>
    <div x-show="loading" x-cloak class="flex justify-center rounded-3xl border border-line bg-white p-5"><x-spinner /></div>
    <div x-show="loadError" x-cloak class="rounded-3xl border border-line bg-white p-5">
        <p class="text-red-700" x-text="loadError"></p>
        <a x-show="sessionExpired" href="{{ route('login') }}" class="mt-3 inline-flex min-h-[44px] items-center text-primary underline">Masuk kembali</a>
        <x-outline-button x-show="!sessionExpired" class="mt-3" @click="retry()" x-bind:disabled="loading || loadingMore">Coba lagi</x-outline-button>
    </div>
    <div x-show="hasMore && !loading && !loadError" x-cloak>
        <x-outline-button @click="load()" x-bind:disabled="loadingMore"><x-spinner size="h-5 w-5" x-show="loadingMore" x-cloak /><span x-show="!loadingMore">Muat lebih banyak</span></x-outline-button>
    </div>
</div>

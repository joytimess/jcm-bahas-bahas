{{-- Modal tulis quote. Butuh state quoting/quoteBody/quoteBusy/quoteError dari listState(). --}}
<div x-show="quoting" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4" @keydown.escape.window="quoting = null" role="dialog" aria-modal="true" aria-label="Quote thread">
    <div class="w-full max-w-lg rounded-3xl border border-line bg-white p-5 sm:p-6" @click.outside="quoting = null">
        <h2 class="text-lg font-bold text-ink">Quote thread</h2>
        <form class="mt-3" @submit.prevent="submitQuote()">
            <textarea x-model="quoteBody" rows="3" maxlength="280" placeholder="Tambahkan pendapatmu..." class="block w-full rounded-2xl border-line focus:border-primary focus:ring-primary"></textarea>
            <template x-if="quoting">
                <div class="mt-3 rounded-2xl border border-line bg-ground p-4">
                    <p class="text-sm font-semibold text-ink" x-text="quoting.user.name"></p>
                    <p class="mt-1 line-clamp-4 whitespace-pre-line break-words text-sm text-ink" x-text="quoting.body"></p>
                </div>
            </template>
            <p class="mt-2 text-sm text-red-700" x-show="quoteError" x-text="quoteError" role="alert"></p>
            <div class="mt-4 flex items-center justify-between">
                <span class="text-sm text-muted" x-text="`${quoteBody.length}/280`"></span>
                <div class="flex gap-2">
                    <x-outline-button @click="quoting = null">Batal</x-outline-button>
                    <x-primary-button x-bind:disabled="quoteBusy || !quoteBody.trim()">Post</x-primary-button>
                </div>
            </div>
        </form>
    </div>
</div>

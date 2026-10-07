<x-app-layout>
    <x-slot name="aside">
        <div class="space-y-4">
            @if ($isDashboard ?? false)
                <x-discovery-search-form id="dashboard-search" :shortcut="true" />
            @endif
            <x-suggestions />
        </div>
    </x-slot>

    @include('threads._api')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/css/glightbox.min.css">
    <script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js"></script>



    <div data-source="{{ $source ?? '/api/threads' }}" x-data="discoveryFeed($el.dataset.source, @js($isDashboard ?? false))" @popstate.window="restoreFeed()" @follow-changed.window="followChanged()">
        <!-- Feed -->
        @if ($isDashboard ?? false)
            <div class="flex gap-2 rounded-3xl border border-line bg-white p-3" role="group" aria-label="Pilihan feed">
                <template x-for="option in [{ value: 'all', label: 'Semua' }, { value: 'following', label: 'Mengikuti' }]" :key="option.value">
                    <button type="button" @click="selectFeed(option.value)" :aria-pressed="(activeFeed === option.value).toString()" :class="activeFeed === option.value ? 'bg-primary-tint text-primary-dark' : 'text-muted hover:bg-ground'" class="min-h-[44px] flex-1 rounded-full px-4 py-2.5 font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" x-text="option.label"></button>
                </template>
            </div>
        @endif
        <div class="space-y-4">
            @if ($isDashboard ?? false)
                <div class="lg:hidden"><x-discovery-search-form id="dashboard-search-mobile" :shortcut="true" /></div>
            @endif
            @isset($heading)
                <h1 class="text-2xl font-black tracking-tight">{{ $heading }}</h1>
            @endisset

            <!-- Compose -->
            <div class="p-5 sm:p-6 bg-white border border-line rounded-3xl">
                <form @submit.prevent="post()">
                    <textarea x-model="body" rows="3" maxlength="280" placeholder="{{ __('Apa yang sedang terjadi?') }}"
                        class="block w-full border-line rounded-2xl focus:border-primary focus:ring-primary"></textarea>

                    <div class="mt-3 flex flex-wrap gap-3" x-show="previews.length" x-cloak>
                        <template x-for="(src, i) in previews" :key="i">
                            <div class="relative">
                                <img :src="src" class="h-20 w-20 object-cover rounded-xl">
                                <button type="button" @click="removeFile(i)"
                                    class="absolute -top-2 -end-2 bg-gray-800 text-white rounded-full h-5 w-5 flex items-center justify-center">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <p class="mt-2 text-sm text-red-700" x-show="error" x-text="error" x-cloak></p>

                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <label class="cursor-pointer text-muted hover:text-ink" title="{{ __('Tambah gambar (maks. 5)') }}">
                                <span class="material-symbols-outlined">image</span>
                                <input type="file" accept="image/*" multiple class="hidden" @change="pick($event)">
                            </label>
                            <span class="text-sm text-muted" x-text="`${body.length}/280`"></span>
                        </div>

                        <x-primary-button x-bind:disabled="posting || !body.trim()">{{ __('Post') }}</x-primary-button>
                    </div>
                </form>
            </div>

            
            <div x-show="postNotice" x-cloak class="rounded-3xl border border-line bg-white p-5" aria-live="polite">
                <p x-text="postNotice" class="text-muted"></p>
                <x-outline-button class="mt-2" @click="postNotice = ''; selectFeed('all')">Lihat tab Semua</x-outline-button>
            </div>
            <template x-for="t in threads" :key="t.id">
                @include('threads._card', ['editable' => true])
            </template>

            <div class="rounded-3xl border border-line bg-white p-6 text-center text-muted" x-show="!loading && !loadError && !threads.length" x-cloak>
                <p x-text="dashboard && activeFeed === 'following' ? 'Belum ada postingan dari akun yang kamu ikuti.' : 'Belum ada thread.'"></p>
                <a x-show="dashboard && activeFeed === 'following'" href="{{ route('search', ['type' => 'users']) }}" class="mt-3 inline-flex min-h-[44px] items-center font-semibold text-primary underline">Cari pengguna</a>
            </div>
            @include('threads._quote-modal')
            <x-discovery-status />
        </div>
    </div>
</x-app-layout>

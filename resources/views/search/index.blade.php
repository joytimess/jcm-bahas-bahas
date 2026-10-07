<x-app-layout x-data="discoverySearch()" @popstate.window="restoreSearch()" @follow-changed.window="syncFollow($event.detail)">
    <x-slot name="aside">
        <div class="space-y-4">
            <x-discovery-search-form id="search-query" />
            <x-suggestions />
        </div>
    </x-slot>
    @include('threads._api')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/css/glightbox.min.css">
    <script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js"></script>

    <div class="space-y-4">
        <h1 class="text-2xl font-black tracking-tight">Pencarian</h1>
        <div class="lg:hidden"><x-discovery-search-form id="search-query-mobile" /></div>
        <div class="flex gap-2 rounded-3xl border border-line bg-white p-3" role="group" aria-label="Jenis hasil pencarian">
            <template x-for="option in [{ value: 'threads', label: 'Postingan' }, { value: 'users', label: 'Pengguna' }]" :key="option.value">
                <button type="button" @click="selectType(option.value)" :aria-pressed="(type === option.value).toString()" :class="type === option.value ? 'bg-primary-tint text-primary-dark' : 'text-muted hover:bg-ground'" class="min-h-[44px] flex-1 rounded-full px-4 py-2.5 font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" x-text="option.label"></button>
            </template>
        </div>
        <p x-show="submittedQuery" x-cloak class="break-words text-sm text-muted" x-text="`Hasil untuk “${submittedQuery}”${loading || loadError ? '' : ` · ${total} hasil`}`"></p>
        <p x-show="!submittedQuery" class="rounded-3xl border border-line bg-white p-6 text-center text-muted">Cari postingan atau pengguna. Masukkan minimal 2 karakter.</p>
        <template x-for="t in threads" :key="t.id">
            @include('threads._card', ['editable' => false])
        </template>
        <template x-for="u in users" :key="u.id">
            <article class="rounded-3xl border border-line bg-white p-5 sm:p-6">
                <div class="flex items-center gap-3">
                    <a :href="profileUrl(u.id)" class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        <x-avatar name-expr="u.name" url-expr="u.avatar_url" size="h-10 w-10" />
                        <div class="min-w-0">
                            <p x-text="u.name" class="truncate font-semibold text-ink"></p>
                            <span x-show="u.is_private" x-cloak class="inline-flex items-center gap-1 rounded-full bg-primary-tint px-2 py-1 text-xs font-semibold text-primary-dark"><span class="material-symbols-outlined" style="font-size:14px" aria-hidden="true">lock</span>Akun privat</span>
                        </div>
                    </a>
                    <span x-show="u.is_me" class="text-sm text-muted" x-cloak>Anda</span>
                    <button type="button" x-show="!u.is_me" x-cloak @click="toggleFollow(u)" :disabled="followBusy[u.id] === true" :aria-pressed="(u.follow_status !== 'none').toString()" :class="u.follow_status === 'none' ? 'border-transparent bg-primary text-white hover:bg-primary-dark' : 'border-ink text-ink hover:bg-ground'" class="inline-flex min-h-[44px] items-center rounded-full border-2 px-4 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-60" x-text="followLabel(u)"></button>
                </div>
                <p x-show="followErrors[u.id]" x-text="followErrors[u.id]" x-cloak role="alert" class="mt-2 text-sm text-red-700"></p>
            </article>
        </template>
        <p x-show="submittedQuery && !loading && !loadError && !(type === 'users' ? users.length : threads.length)" x-cloak class="rounded-3xl border border-line bg-white p-6 text-center text-muted" x-text="type === 'users' ? 'Tidak ada pengguna yang cocok. Coba nama lain.' : 'Tidak ada postingan yang cocok. Coba kata lain.'"></p>
        <x-discovery-status />
    </div>
</x-app-layout>

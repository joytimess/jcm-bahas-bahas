{{-- Kolom kanan: 5 pengguna terbaru + tombol Ikuti. Butuh window.api dan profileUrl (threads._api). --}}
<script>
    function suggestions() {
        return {
            users: [],
            loading: true,
            busyId: null,
            errors: {},
            loadError: '',
            sessionExpired: false,

            async init() {
                this.loading = true; this.loadError = '';
                try {
                    this.users = (await api('GET', '/api/users/suggestions')).data;
                } catch (e) {
                    this.users = [];
                    this.sessionExpired = [401, 419].includes(e.status);
                    this.loadError = window.discoveryError(e);
                }
                this.loading = false;
            },

            async toggleFollow(u) {
                if (this.busyId) return;
                this.busyId = u.id;
                this.errors[u.id] = '';
                try {
                    const res = await api('POST', `/api/users/${u.id}/follow`);
                    u.follow_status = res.status;
                    window.dispatchEvent(new CustomEvent('follow-changed', { detail: { userId: u.id, status: res.status } }));
                } catch (e) { this.sessionExpired = [401, 419].includes(e.status); this.errors[u.id] = window.discoveryError(e); }
                this.busyId = null;
            },

            label(u) {
                return { none: 'Ikuti', pending: 'Diminta', following: 'Mengikuti' }[u.follow_status];
            },
            syncFollow(detail) {
                this.users.forEach(u => { if (u.id === detail.userId) u.follow_status = detail.status; });
            },
        };
    }
</script>

<section x-data="suggestions()" @follow-changed.window="syncFollow($event.detail)" aria-labelledby="suggestions-title" class="rounded-3xl border border-line bg-white p-5">
    <h2 id="suggestions-title" class="text-lg font-bold">Pengguna baru</h2>

    <div class="mt-4 flex justify-center" x-show="loading" x-cloak><x-spinner size="h-6 w-6" /></div>
    <p class="mt-4 text-sm text-muted" x-show="!loading && !loadError && !users.length" x-cloak>Belum ada pengguna lain.</p>
    <p class="mt-4 text-sm text-red-700" x-show="loadError" x-text="loadError" x-cloak role="alert"></p>
    <x-outline-button x-show="loadError && !sessionExpired" class="mt-2" @click="init()">Coba lagi</x-outline-button>
    <a x-show="sessionExpired" x-cloak href="{{ route('login') }}" class="mt-2 inline-flex min-h-[44px] items-center text-primary underline">Masuk kembali</a>

    <ul class="mt-4 space-y-3" x-show="users.length" x-cloak>
        <template x-for="u in users" :key="u.id">
            <li class="flex flex-wrap items-center gap-3">
                <a :href="profileUrl(u.id)" class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <x-avatar name-expr="u.name" url-expr="u.avatar_url" size="h-10 w-10" />
                    <span class="min-w-0 flex-1 truncate font-semibold hover:underline" x-text="u.name"></span>
                </a>
                <button type="button" @click="toggleFollow(u)" :disabled="busyId !== null"
                        :aria-pressed="(u.follow_status !== 'none').toString()"
                        class="inline-flex min-h-[44px] items-center rounded-full px-4 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60"
                        :class="u.follow_status === 'none' ? 'bg-primary text-white hover:bg-primary-dark' : 'border-2 border-ink text-ink hover:bg-ground'"
                        x-text="label(u)"></button>
                <p x-show="errors[u.id]" x-text="errors[u.id]" x-cloak class="w-full text-sm text-red-700" role="alert"></p>
            </li>
        </template>
    </ul>
</section>

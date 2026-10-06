{{-- Kolom kanan: 5 pengguna terbaru + tombol Ikuti. Butuh window.api dan profileUrl (threads._api). --}}
<script>
    function suggestions() {
        return {
            users: [],
            loading: true,
            busyId: null,

            async init() {
                try {
                    this.users = (await api('GET', '/api/users/suggestions')).data;
                } catch (e) {
                    this.users = [];
                }
                this.loading = false;
            },

            async toggleFollow(u) {
                if (this.busyId) return;
                this.busyId = u.id;
                try {
                    const res = await api('POST', `/api/users/${u.id}/follow`);
                    u.follow_status = res.status;
                } catch (e) {}
                this.busyId = null;
            },

            label(u) {
                return { none: 'Ikuti', pending: 'Diminta', following: 'Mengikuti' }[u.follow_status];
            },
        };
    }
</script>

<section x-data="suggestions()" aria-labelledby="suggestions-title" class="rounded-3xl border border-line bg-white p-5">
    <h2 id="suggestions-title" class="text-lg font-bold">Pengguna baru</h2>

    <p class="mt-4 text-sm text-muted" x-show="loading" x-cloak>Memuat...</p>
    <p class="mt-4 text-sm text-muted" x-show="!loading && !users.length" x-cloak>Belum ada pengguna lain.</p>

    <ul class="mt-4 space-y-3" x-show="users.length" x-cloak>
        <template x-for="u in users" :key="u.id">
            <li class="flex items-center gap-3">
                <a :href="profileUrl(u.id)" class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <x-avatar name-expr="u.name" url-expr="u.avatar_url" size="h-10 w-10" />
                    <span class="min-w-0 flex-1 truncate font-semibold hover:underline" x-text="u.name"></span>
                </a>
                <button type="button" @click="toggleFollow(u)" :disabled="busyId === u.id"
                        :aria-pressed="(u.follow_status !== 'none').toString()"
                        class="inline-flex min-h-[36px] items-center rounded-full px-4 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60"
                        :class="u.follow_status === 'none' ? 'bg-primary text-white hover:bg-primary-dark' : 'border-2 border-ink text-ink hover:bg-ground'"
                        x-text="label(u)"></button>
            </li>
        </template>
    </ul>
</section>

{{--
    Popup daftar pengikut / mengikuti dengan pencarian.
    Buka dengan: $dispatch('open-follow-list', { userId: 1, type: 'followers' | 'following' })
--}}
<script>
    function followListModal() {
        return {
            open: false,
            userId: null,
            type: 'followers',
            search: '',
            users: [],
            page: 1,
            hasMore: false,
            loading: false,
            error: '',
            seq: 0,

            get title() { return this.type === 'followers' ? 'Pengikut' : 'Mengikuti'; },

            init() {
                this.$watch('search', () => this.reload());
                this.$watch('open', v => document.documentElement.classList.toggle('overflow-hidden', v));
            },

            show(detail) {
                this.userId = detail.userId;
                this.type = detail.type;
                this.open = true;
                this.search = '';
                this.reload();
                this.$nextTick(() => this.$refs.search.focus());
            },

            async reload() {
                if (!this.open) return;
                this.page = 1;
                this.users = [];
                await this.load();
            },

            async load() {
                const seq = ++this.seq;
                this.loading = true;
                this.error = '';
                try {
                    const q = new URLSearchParams({ page: this.page, search: this.search });
                    const res = await api('GET', `/api/users/${this.userId}/${this.type}?${q}`);
                    if (seq !== this.seq) return; // respons lama dari ketikan sebelumnya
                    this.users.push(...res.data);
                    this.hasMore = res.current_page < res.last_page;
                } catch (e) {
                    if (seq === this.seq) this.error = e.message;
                }
                if (seq === this.seq) this.loading = false;
            },

            async loadMore() { this.page++; await this.load(); },
        };
    }
</script>

<div x-data="followListModal()" x-show="open" x-cloak
     @open-follow-list.window="show($event.detail)"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-50 flex items-end justify-center bg-ink/70 sm:items-center sm:p-6"
     role="dialog" aria-modal="true" aria-labelledby="follow-list-title">
    <div @click.outside="open = false" class="flex max-h-[85dvh] w-full max-w-md flex-col overflow-hidden rounded-t-3xl bg-white sm:rounded-3xl">
        <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-4">
            <h2 id="follow-list-title" class="text-lg font-bold" x-text="title"></h2>
            <button type="button" @click="open = false" aria-label="Tutup"
                    class="flex h-11 w-11 items-center justify-center rounded-full text-muted hover:bg-ground hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <div class="border-b border-line px-5 py-3">
            <label for="follow-list-search" class="sr-only">Cari nama</label>
            <div class="relative">
                <span class="material-symbols-outlined pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-muted" aria-hidden="true">search</span>
                <input id="follow-list-search" x-ref="search" type="search" x-model.debounce.300ms="search" placeholder="Cari nama..."
                       class="block w-full rounded-full border-line bg-ground py-2.5 ps-11 pe-4 focus:border-primary focus:ring-primary">
            </div>
        </div>

        <div class="min-h-[200px] flex-1 overflow-y-auto px-5 py-3">
            <ul class="space-y-1">
                <template x-for="u in users" :key="u.id">
                    <li>
                        <a :href="profileUrl(u.id)" class="flex min-h-[52px] items-center gap-3 rounded-2xl px-2 hover:bg-ground focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                            <x-avatar name-expr="u.name" url-expr="u.avatar_url" size="h-10 w-10" />
                            <span class="min-w-0 flex-1 truncate font-semibold" x-text="u.name"></span>
                        </a>
                    </li>
                </template>
            </ul>

            <div class="flex justify-center py-6" x-show="loading"><x-spinner /></div>
            <p class="py-6 text-center text-sm text-red-700" x-show="!loading && error" x-text="error"></p>
            <p class="py-6 text-center text-sm text-muted" x-show="!loading && !error && !users.length"
               x-text="search ? 'Tidak ada yang cocok.' : (type === 'followers' ? 'Belum ada pengikut.' : 'Belum mengikuti siapa pun.')"></p>

            <div class="py-2 text-center" x-show="hasMore && !loading">
                <x-secondary-button type="button" @click="loadMore()">Muat lebih banyak</x-secondary-button>
            </div>
        </div>
    </div>
</div>

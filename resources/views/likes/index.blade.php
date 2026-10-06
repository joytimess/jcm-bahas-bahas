<x-app-layout>
    <x-slot name="aside">
        <x-suggestions />
    </x-slot>

    @include('threads._api')

    <script>
        function myLikes() {
            return {
                items: [],
                page: 1,
                hasMore: false,
                loading: true,

                async init() { await this.load(); },

                async load() {
                    this.loading = true;
                    const res = await api('GET', `/api/me/likes?page=${this.page}`);
                    this.items.push(...res.data);
                    this.hasMore = res.current_page < res.last_page;
                    this.loading = false;
                },

                async loadMore() { this.page++; await this.load(); },
            };
        }
    </script>

    <div x-data="myLikes()" class="space-y-4">
        <h1 class="text-2xl font-black tracking-tight">My Likes</h1>

        <template x-for="l in items" :key="l.id">
            <article class="rounded-3xl border border-line bg-white p-5 sm:p-6">
                <p class="flex items-center gap-2 text-sm text-muted">
                    <span class="material-symbols-outlined text-red-500" style="font-size: 18px;" aria-hidden="true">favorite</span>
                    <span x-text="(l.type === 'comment' ? 'Kamu menyukai komentar' : 'Kamu menyukai thread') + ' · ' + timeAgo(l.liked_at)"></span>
                </p>

                {{-- Like komentar: tampilkan komentar, lalu thread induknya sebagai rujukan --}}
                <template x-if="l.comment">
                    <div class="mt-3">
                        <div class="flex items-center gap-2">
                            <a :href="profileUrl(l.comment.user.id)" class="rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" :aria-label="l.comment.user.name">
                                <x-avatar name-expr="l.comment.user.name" url-expr="l.comment.user.avatar_url" size="h-8 w-8" text="text-xs" />
                            </a>
                            <a :href="profileUrl(l.comment.user.id)" class="font-semibold hover:underline" x-text="l.comment.user.name"></a>
                        </div>
                        <a :href="`/threads/${l.thread.id}`" class="mt-2 block whitespace-pre-line break-words text-ink" x-text="l.comment.body"></a>
                    </div>
                </template>

                <a :href="`/threads/${l.thread.id}`"
                   class="mt-3 block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                   :class="l.comment ? 'border border-line bg-ground p-4' : ''">
                    <p x-show="l.comment" class="mb-2 text-xs font-bold uppercase tracking-widest text-primary">Pada thread</p>
                    <div class="flex items-center gap-2">
                        <x-avatar name-expr="l.thread.user.name" url-expr="l.thread.user.avatar_url" size="h-8 w-8" text="text-xs" />
                        <span class="font-semibold" x-text="l.thread.user.name"></span>
                    </div>
                    <p class="mt-2 whitespace-pre-line break-words" :class="l.comment ? 'text-muted' : 'text-ink'" x-text="l.thread.body"></p>
                </a>
            </article>
        </template>

        <p class="text-center text-muted" x-show="loading" x-cloak>Memuat...</p>
        <p class="text-center text-muted" x-show="!loading && !items.length" x-cloak>Belum ada yang kamu sukai.</p>

        <div class="text-center" x-show="hasMore && !loading" x-cloak>
            <x-secondary-button type="button" @click="loadMore()">Muat lebih banyak</x-secondary-button>
        </div>
    </div>
</x-app-layout>

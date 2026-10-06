<x-app-layout>
    <x-slot name="aside">
        <x-suggestions />
    </x-slot>

    @include('threads._api')
    <x-follow-list-modal />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/css/glightbox.min.css">
    <script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js"></script>

    <script>
        function userProfile(userId) {
            return {
                profile: null,
                threads: [],
                page: 1,
                hasMore: false,
                loading: true,
                busy: false,

                async init() {
                    this.profile = (await api('GET', `/api/users/${userId}`)).data;
                    if (this.profile.can_view_threads) await this.load();
                    this.loading = false;
                },

                async load() {
                    const res = await api('GET', `/api/users/${userId}/threads?page=${this.page}`);
                    this.threads.push(...res.data);
                    this.hasMore = res.meta.current_page < res.meta.last_page;
                },

                async loadMore() {
                    this.page++;
                    this.loading = true;
                    await this.load();
                    this.loading = false;
                },

                async toggleFollow() {
                    if (this.busy) return;
                    this.busy = true;
                    try {
                        const res = await api('POST', `/api/users/${userId}/follow`);
                        this.profile.follow_status = res.status;
                        this.profile.followers_count = res.followers_count;
                        const canView = ! this.profile.is_private || res.status === 'following';
                        if (canView !== this.profile.can_view_threads) {
                            this.profile.can_view_threads = canView;
                            this.threads = [];
                            this.page = 1;
                            if (canView) await this.load();
                        }
                    } catch (e) {}
                    this.busy = false;
                },

                followLabel() {
                    return { none: 'Ikuti', pending: 'Diminta', following: 'Mengikuti' }[this.profile.follow_status];
                },

                async like(t) {
                    const res = await api('POST', `/api/threads/${t.id}/like`);
                    t.liked_by_me = res.liked;
                    t.likes_count = res.likes_count;
                },

                openImage(images, index) {
                    GLightbox({
                        elements: images.map(i => ({ href: i.url, type: 'image' })),
                        startAt: index,
                        loop: true,
                    }).open();
                },
            };
        }
    </script>

    <div x-data="userProfile({{ $profileId }})">
        <div class="space-y-4">
            <a href="{{ route('dashboard') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-full pe-4 font-semibold text-muted hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                Kembali ke dashboard
            </a>

            <p class="text-center text-muted" x-show="!profile" x-cloak>Memuat...</p>

            <!-- Header profil -->
            <template x-if="profile">
                <section aria-label="Profil" class="flex flex-wrap items-center gap-6 rounded-3xl border border-line bg-white p-6">
                    <x-avatar name-expr="profile.name" url-expr="profile.avatar_url" size="h-24 w-24" text="text-3xl" />

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="truncate text-2xl font-black tracking-tight" x-text="profile.name"></h1>
                            <span x-show="profile.is_private" x-cloak class="inline-flex items-center gap-1 rounded-full bg-primary-tint px-3 py-1 text-xs font-bold text-primary-dark">
                                <span class="material-symbols-outlined" style="font-size: 14px;" aria-hidden="true">lock</span>
                                Akun privat
                            </span>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <template x-for="t in ['followers', 'following']" :key="t">
                                <button type="button" :disabled="!profile.can_view_threads"
                                        @click="$dispatch('open-follow-list', { userId: profile.id, type: t })"
                                        :title="profile.can_view_threads ? '' : 'Akun ini privat'"
                                        class="inline-flex min-h-[44px] items-baseline gap-1.5 rounded-full px-2 py-2 hover:bg-ground focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-default disabled:hover:bg-transparent">
                                    <span class="text-xl font-black" x-text="t === 'followers' ? profile.followers_count : profile.following_count"></span>
                                    <span class="text-muted" x-text="t === 'followers' ? 'pengikut' : 'mengikuti'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <button type="button" @click="toggleFollow()" :disabled="busy" :aria-pressed="(profile.follow_status !== 'none').toString()"
                            class="inline-flex min-h-[44px] items-center rounded-full px-6 font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60"
                            :class="profile.follow_status === 'none' ? 'bg-primary text-white hover:bg-primary-dark' : 'border-2 border-ink text-ink hover:bg-ground'"
                            x-text="followLabel()"></button>
                </section>
            </template>

            <!-- Akun privat, belum disetujui -->
            <div x-show="profile && !profile.can_view_threads" x-cloak class="rounded-3xl border border-line bg-white p-8 text-center">
                <span class="material-symbols-outlined text-primary" style="font-size: 40px;" aria-hidden="true">lock</span>
                <p class="mt-2 text-lg font-bold">Akun ini privat</p>
                <p class="mt-1 text-muted">Ikuti dan tunggu persetujuan untuk melihat thread-nya.</p>
            </div>

            <!-- Thread -->
            <template x-for="t in threads" :key="t.id">
                <div class="p-5 sm:p-6 bg-white border border-line rounded-3xl">
                    <div class="flex items-center gap-3">
                        <x-avatar name-expr="t.user.name" url-expr="t.user.avatar_url" size="h-10 w-10" />
                        <div>
                            <div class="font-medium text-ink" x-text="t.user.name"></div>
                            <div class="text-xs text-muted" x-text="timeAgo(t.created_at)"></div>
                        </div>
                    </div>

                    <a :href="`/threads/${t.id}`" class="block mt-3 text-ink whitespace-pre-line break-words" x-text="t.body"></a>

                    <div class="mt-3 grid gap-2 items-start" :class="t.images.length > 1 ? 'grid-cols-2' : 'grid-cols-1'" x-show="t.images.length">
                        <template x-for="(img, i) in t.images" :key="img.id">
                            <img :src="img.url" alt="" @click="openImage(t.images, i)" class="w-full h-auto rounded-2xl cursor-zoom-in">
                        </template>
                    </div>

                    <div class="mt-4 flex items-center gap-6 text-sm text-muted">
                        <button @click="like(t)" class="inline-flex min-h-[36px] items-center gap-1 hover:text-red-500" :class="t.liked_by_me && 'text-red-500'">
                            <span class="material-symbols-outlined" style="font-size: 20px;" aria-hidden="true">favorite</span>
                            <span x-text="t.likes_count"></span>
                        </button>
                        <a :href="`/threads/${t.id}`" class="inline-flex min-h-[36px] items-center gap-1 hover:text-ink">
                            <span class="material-symbols-outlined" style="font-size: 20px;" aria-hidden="true">chat_bubble</span>
                            <span x-text="t.comments_count"></span>
                        </a>
                    </div>
                </div>
            </template>

            <p class="text-center text-muted" x-show="profile && profile.can_view_threads && !loading && !threads.length" x-cloak>Belum ada thread.</p>

            <div class="text-center" x-show="hasMore && !loading" x-cloak>
                <x-secondary-button type="button" @click="loadMore()">Muat lebih banyak</x-secondary-button>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="aside">
        <x-suggestions />
    </x-slot>

    @include('threads._api')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/css/glightbox.min.css">
    <script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js"></script>

    <script>
        function feed() {
            return {
                ...imagePicker(),
                threads: [],
                page: 1,
                hasMore: false,
                loading: true,
                body: '',
                posting: false,
                error: '',
                editingId: null,
                editBody: '',

                async init() { await this.load(); },

                async load() {
                    this.loading = true;
                    const res = await api('GET', `{{ $source ?? '/api/threads' }}?page=${this.page}`);
                    this.threads.push(...res.data);
                    this.hasMore = res.meta.current_page < res.meta.last_page;
                    this.loading = false;
                },

                async loadMore() { this.page++; await this.load(); },

                async post() {
                    this.error = '';
                    this.posting = true;
                    const fd = new FormData();
                    fd.append('body', this.body);
                    this.files.forEach(f => fd.append('images[]', f));
                    try {
                        const res = await api('POST', '/api/threads', fd);
                        this.threads.unshift(res.data);
                        this.body = '';
                        this.reset();
                    } catch (e) {
                        this.error = Object.values(e.errors).flat()[0] || e.message;
                    }
                    this.posting = false;
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

                startEdit(t) { this.editingId = t.id; this.editBody = t.body; },

                async saveEdit(t) {
                    const res = await api('PUT', `/api/threads/${t.id}`, { body: this.editBody });
                    Object.assign(t, res.data);
                    this.editingId = null;
                },

                async remove(t) {
                    if (!confirm('Hapus thread ini?')) return;
                    await api('DELETE', `/api/threads/${t.id}`);
                    this.threads = this.threads.filter(x => x.id !== t.id);
                },
            };
        }
    </script>

    <div x-data="feed()">
        <div class="space-y-4">
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

            <!-- Feed -->
            <template x-for="t in threads" :key="t.id">
                <div class="p-5 sm:p-6 bg-white border border-line rounded-3xl">
                    <div class="flex items-start justify-between">
                        <a :href="profileUrl(t.user.id)" class="flex items-center gap-3 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                            <x-avatar name-expr="t.user.name" url-expr="t.user.avatar_url" size="h-10 w-10" />
                            <div>
                                <div class="font-medium text-ink hover:underline" x-text="t.user.name"></div>
                                <div class="text-xs text-muted" x-text="timeAgo(t.created_at)"></div>
                            </div>
                        </a>

                        <div class="relative" x-data="{ open: false }" x-show="t.user.id === ME" x-cloak @click.outside="open = false" @keydown.escape.window="open = false">
                            <button @click="open = !open" class="p-1 rounded-full text-muted hover:bg-ground" title="Actions">
                                <span class="material-symbols-outlined">more_vert</span>
                            </button>

                            <div x-show="open" x-cloak x-transition.origin.top.right
                                class="absolute end-0 z-20 mt-1 w-40 rounded-2xl shadow-lg bg-white border border-line p-1">
                                <button @click="startEdit(t); open = false" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm leading-5 text-ink hover:bg-ground">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">edit</span>
                                    {{ __('Edit') }}
                                </button>
                                <button @click="open = false; remove(t)" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm leading-5 text-red-700 hover:bg-ground">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Body / edit -->
                    <template x-if="editingId !== t.id">
                        <a :href="`/threads/${t.id}`" class="block mt-3 text-ink whitespace-pre-line break-words" x-text="t.body"></a>
                    </template>
                    <template x-if="editingId === t.id">
                        <div class="mt-3">
                            <textarea x-model="editBody" rows="3" maxlength="280"
                                class="block w-full border-line rounded-2xl focus:border-primary focus:ring-primary"></textarea>
                            <div class="mt-2 flex gap-2">
                                <x-primary-button type="button" @click="saveEdit(t)">{{ __('Save') }}</x-primary-button>
                                <x-secondary-button type="button" @click="editingId = null">{{ __('Cancel') }}</x-secondary-button>
                            </div>
                        </div>
                    </template>

                    <!-- Images -->
                    <div class="mt-3 grid gap-2 items-start" :class="t.images.length > 1 ? 'grid-cols-2' : 'grid-cols-1'" x-show="t.images.length">
                        <template x-for="(img, i) in t.images" :key="img.id">
                            <img :src="img.url" @click="openImage(t.images, i)" class="w-full h-auto rounded-2xl cursor-zoom-in">
                        </template>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex items-center gap-6 text-sm text-muted">
                        <button @click="like(t)" class="inline-flex items-center gap-1 hover:text-red-500" :class="t.liked_by_me && 'text-red-500'">
                            <span class="material-symbols-outlined" style="font-size: 20px;">favorite</span>
                            <span x-text="t.likes_count"></span>
                        </button>
                        <a :href="`/threads/${t.id}`" class="inline-flex items-center gap-1 hover:text-ink">
                            <span class="material-symbols-outlined" style="font-size: 20px;">chat_bubble</span>
                            <span x-text="t.comments_count"></span>
                        </a>
                    </div>
                </div>
            </template>

            <p class="text-center text-muted" x-show="loading" x-cloak>{{ __('Memuat...') }}</p>
            <p class="text-center text-muted" x-show="!loading && !threads.length" x-cloak>{{ __('Belum ada thread.') }}</p>

            <div class="text-center" x-show="hasMore && !loading" x-cloak>
                <x-secondary-button type="button" @click="loadMore()">{{ __('Muat lebih banyak') }}</x-secondary-button>
            </div>
        </div>
    </div>
</x-app-layout>

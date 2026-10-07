<x-app-layout>
    <x-slot name="aside">
        <x-suggestions />
    </x-slot>

    @include('threads._api')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/css/glightbox.min.css">
    <script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js"></script>

    <script>
        function threadPage(threadId) {
            return {
                ...imagePicker(),
                thread: null,
                comments: [],      // flat, sudah berurutan sesuai tree + depth
                body: '',
                replyTo: null,
                posting: false,
                error: '',
                editingId: null,
                editBody: '',
                threadEditing: false,
                threadEditBody: '',
                repostBusy: {},
                quoting: null,
                quoteBody: '',
                quoteBusy: false,
                quoteError: '',

                async init() {
                    this.thread = (await api('GET', `/api/threads/${threadId}`)).data;
                    // Halaman repost diarahkan ke thread aslinya.
                    if (this.thread.type === 'repost' && this.thread.repost_of) return window.location.replace(`/threads/${this.thread.repost_of.id}`);
                    await this.loadComments();
                },

                async loadComments() {
                    const tree = (await api('GET', `/api/threads/${threadId}/comments`)).data;
                    const flat = [];
                    const walk = (nodes, depth) => nodes.forEach(n => {
                        flat.push({ ...n, depth });
                        walk(n.replies || [], depth + 1);
                    });
                    walk(tree, 0);
                    this.comments = flat;
                    this.thread.comments_count = flat.length;
                },

                openImage(images, index) {
                    GLightbox({
                        elements: images.map(i => ({ href: i.url, type: 'image' })),
                        startAt: index,
                        loop: true,
                    }).open();
                },

                async like(item, url) {
                    const res = await api('POST', url);
                    item.liked_by_me = res.liked;
                    item.likes_count = res.likes_count;
                },

                async repost(v) {
                    if (this.repostBusy[v.id]) return;
                    this.repostBusy[v.id] = true; this.error = '';
                    try {
                        const res = await api('POST', `/api/threads/${v.id}/repost`);
                        v.reposted_by_me = res.reposted;
                        v.reposts_count = res.reposts_count;
                    } catch (e) { this.error = e.message; }
                    finally { this.repostBusy[v.id] = false; }
                },

                startQuote(v) { this.quoting = v; this.quoteBody = ''; this.quoteError = ''; },

                async submitQuote() {
                    if (this.quoteBusy || !this.quoting || !this.quoteBody.trim()) return;
                    this.quoteBusy = true; this.quoteError = '';
                    try {
                        const res = await api('POST', '/api/threads', { body: this.quoteBody, quote_of: this.quoting.id });
                        window.location.assign(`/threads/${res.data.id}`);
                    } catch (e) { this.quoteError = Object.values(e.errors || {}).flat()[0] || e.message; }
                    finally { this.quoteBusy = false; }
                },

                async saveThread() {
                    const res = await api('PUT', `/api/threads/${threadId}`, { body: this.threadEditBody });
                    Object.assign(this.thread, res.data);
                    this.threadEditing = false;
                },

                async removeThread() {
                    if (!confirm('Hapus thread ini?')) return;
                    await api('DELETE', `/api/threads/${threadId}`);
                    window.location = '{{ route('dashboard') }}';
                },

                async postComment() {
                    this.error = '';
                    this.posting = true;
                    const fd = new FormData();
                    fd.append('body', this.body);
                    if (this.replyTo) fd.append('parent_id', this.replyTo.id);
                    this.files.forEach(f => fd.append('images[]', f));
                    try {
                        await api('POST', `/api/threads/${threadId}/comments`, fd);
                        this.body = '';
                        this.replyTo = null;
                        this.reset();
                        await this.loadComments();
                    } catch (e) {
                        this.error = Object.values(e.errors).flat()[0] || e.message;
                    }
                    this.posting = false;
                },

                async saveComment(c) {
                    const res = await api('PUT', `/api/comments/${c.id}`, { body: this.editBody });
                    c.body = res.data.body;
                    this.editingId = null;
                },

                async removeComment(c) {
                    if (!confirm('Hapus komentar ini beserta balasannya?')) return;
                    await api('DELETE', `/api/comments/${c.id}`);
                    await this.loadComments();
                },
            };
        }
    </script>

    <div x-data="threadPage({{ $threadId }})">
        <div class="space-y-4">
            <a href="{{ route('dashboard') }}" class="inline-flex min-h-[44px] items-center gap-2 rounded-full pe-4 font-semibold text-muted hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                Kembali ke dashboard
            </a>

            <!-- Thread -->
            <template x-if="thread">
                <div class="p-5 sm:p-6 bg-white border border-line rounded-3xl">
                    <div class="flex items-start justify-between">
                        <a :href="profileUrl(thread.user.id)" class="flex items-center gap-3 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                            <x-avatar name-expr="thread.user.name" url-expr="thread.user.avatar_url" size="h-10 w-10" />
                            <div>
                                <div class="font-medium text-ink hover:underline" x-text="thread.user.name"></div>
                                <div class="text-xs text-muted" x-text="timeAgo(thread.created_at)"></div>
                            </div>
                        </a>
                        <div class="relative" x-data="{ open: false }" x-show="thread.user.id === ME" x-cloak @click.outside="open = false" @keydown.escape.window="open = false">
                            <button @click="open = !open" class="p-1 rounded-full text-muted hover:bg-ground" title="Actions">
                                <span class="material-symbols-outlined">more_vert</span>
                            </button>

                            <div x-show="open" x-cloak x-transition.origin.top.right
                                class="absolute end-0 z-20 mt-1 w-40 rounded-2xl shadow-lg bg-white border border-line p-1">
                                <button @click="threadEditing = true; threadEditBody = thread.body; open = false" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm leading-5 text-ink hover:bg-ground">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">edit</span>
                                    {{ __('Edit') }}
                                </button>
                                <button @click="open = false; removeThread()" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm leading-5 text-red-700 hover:bg-ground">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-ink whitespace-pre-line break-words" x-show="!threadEditing" x-text="thread.body"></p>

                    <div class="mt-3" x-show="threadEditing" x-cloak>
                        <textarea x-model="threadEditBody" rows="3" maxlength="280"
                            class="block w-full border-line rounded-2xl focus:border-primary focus:ring-primary"></textarea>
                        <div class="mt-2 flex gap-2">
                            <x-primary-button type="button" @click="saveThread()">{{ __('Save') }}</x-primary-button>
                            <x-secondary-button type="button" @click="threadEditing = false">{{ __('Cancel') }}</x-secondary-button>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-2 items-start" :class="thread.images.length > 1 ? 'grid-cols-2' : 'grid-cols-1'" x-show="thread.images.length">
                        <template x-for="(img, i) in thread.images" :key="img.id">
                            <img :src="img.url" @click="openImage(thread.images, i)" class="w-full h-auto rounded-2xl cursor-zoom-in">
                        </template>
                    </div>

                    @include('threads._quote-embed', ['expr' => 'thread'])

                    <div class="mt-4 flex items-center gap-6 text-sm text-muted">
                        <button @click="like(thread, `/api/threads/${thread.id}/like`)" class="inline-flex items-center gap-1 hover:text-red-500" :class="thread.liked_by_me && 'text-red-500'">
                            <span class="material-symbols-outlined" style="font-size: 20px;">favorite</span>
                            <span x-text="thread.likes_count"></span>
                        </button>
                        <span class="inline-flex items-center gap-1">
                            <span class="material-symbols-outlined" style="font-size: 20px;">chat_bubble</span>
                            <span x-text="thread.comments_count"></span>
                        </span>
                        @include('threads._repost-menu', ['expr' => 'thread'])
                    </div>
                </div>
            </template>

            @include('threads._quote-modal')

            <!-- Compose comment -->
            <div class="p-5 sm:p-6 bg-white border border-line rounded-3xl">
                <div class="mb-2 text-sm text-muted" x-show="replyTo" x-cloak>
                    {{ __('Membalas') }} <span class="font-medium" x-text="replyTo?.user.name"></span>
                    <button type="button" class="ms-2 underline" @click="replyTo = null">{{ __('Batal') }}</button>
                </div>

                <form @submit.prevent="postComment()">
                    <textarea x-model="body" rows="2" maxlength="280" placeholder="{{ __('Tulis komentar...') }}"
                        class="block w-full border-line rounded-2xl focus:border-primary focus:ring-primary"></textarea>

                    <div class="mt-3 flex flex-wrap gap-3" x-show="previews.length" x-cloak>
                        <template x-for="(src, i) in previews" :key="i">
                            <div class="relative">
                                <img :src="src" class="h-20 w-20 object-cover rounded-xl">
                                <button type="button" @click="removeFile(i)" class="absolute -top-2 -end-2 bg-gray-800 text-white rounded-full h-5 w-5 flex items-center justify-center">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <p class="mt-2 text-sm text-red-700" x-show="error" x-text="error" x-cloak></p>

                    <div class="mt-4 flex items-center justify-between">
                        <label class="cursor-pointer text-muted hover:text-ink" title="{{ __('Tambah gambar (maks. 5)') }}">
                            <span class="material-symbols-outlined">image</span>
                            <input type="file" accept="image/*" multiple class="hidden" @change="pick($event)">
                        </label>
                        <x-primary-button x-bind:disabled="posting || !body.trim()">{{ __('Reply') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Comments -->
            <div class="bg-white border border-line rounded-3xl overflow-hidden divide-y divide-line" x-show="comments.length" x-cloak>
                <template x-for="c in comments" :key="c.id">
                    <div class="p-4 sm:p-6" :style="`padding-inline-start: ${Math.min(c.depth, 6) * 1.5 + 1.5}rem`">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <a :href="profileUrl(c.user.id)" class="rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" :aria-label="c.user.name">
                                    <x-avatar name-expr="c.user.name" url-expr="c.user.avatar_url" size="h-8 w-8" text="text-xs" />
                                </a>
                                <div>
                                    <a :href="profileUrl(c.user.id)" class="font-medium text-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" x-text="c.user.name"></a>
                                    <span class="ms-2 text-xs text-muted" x-text="timeAgo(c.created_at)"></span>
                                </div>
                            </div>
                            <div class="relative" x-data="{ open: false }" x-show="c.user.id === ME" x-cloak @click.outside="open = false" @keydown.escape.window="open = false">
                            <button @click="open = !open" class="p-1 rounded-full text-muted hover:bg-ground" title="Actions">
                                <span class="material-symbols-outlined">more_vert</span>
                            </button>

                            <div x-show="open" x-cloak x-transition.origin.top.right
                                class="absolute end-0 z-20 mt-1 w-40 rounded-2xl shadow-lg bg-white border border-line p-1">
                                <button @click="editingId = c.id; editBody = c.body; open = false" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm leading-5 text-ink hover:bg-ground">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">edit</span>
                                    {{ __('Edit') }}
                                </button>
                                <button @click="open = false; removeComment(c)" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm leading-5 text-red-700 hover:bg-ground">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                        </div>

                        <p class="mt-2 text-ink whitespace-pre-line break-words" x-show="editingId !== c.id" x-text="c.body"></p>

                        <div class="mt-2" x-show="editingId === c.id" x-cloak>
                            <textarea x-model="editBody" rows="2" maxlength="280"
                                class="block w-full border-line rounded-2xl focus:border-primary focus:ring-primary"></textarea>
                            <div class="mt-2 flex gap-2">
                                <x-primary-button type="button" @click="saveComment(c)">{{ __('Save') }}</x-primary-button>
                                <x-secondary-button type="button" @click="editingId = null">{{ __('Cancel') }}</x-secondary-button>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-2" x-show="c.images.length">
                            <template x-for="(img, i) in c.images" :key="img.id">
                                <img :src="img.url" @click="openImage(c.images, i)" class="max-h-48 w-auto max-w-full h-auto rounded-2xl cursor-zoom-in">
                            </template>
                        </div>

                        <div class="mt-3 flex items-center gap-6 text-sm text-muted">
                            <button @click="like(c, `/api/comments/${c.id}/like`)" class="inline-flex items-center gap-1 hover:text-red-500" :class="c.liked_by_me && 'text-red-500'">
                                <span class="material-symbols-outlined" style="font-size: 18px;">favorite</span>
                                <span x-text="c.likes_count"></span>
                            </button>
                            <button @click="replyTo = c; window.scrollTo({ top: 0, behavior: 'smooth' })" class="inline-flex items-center gap-1 hover:text-ink">
                                <span class="material-symbols-outlined" style="font-size: 18px;">reply</span>
                                {{ __('Reply') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>

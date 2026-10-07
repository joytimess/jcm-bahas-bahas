                {{-- t = baris feed; v = thread yang ditampilkan (thread asli bila t adalah repost). --}}
                <div class="p-5 sm:p-6 bg-white border border-line rounded-3xl" x-data="{ get v() { return t.type === 'repost' ? t.repost_of : t } }">
                    <template x-if="t.type === 'repost'">
                        <div class="mb-3 flex items-center justify-between gap-2 text-sm text-muted">
                            <a :href="profileUrl(t.user.id)" class="inline-flex items-center gap-1 rounded-full hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <span class="material-symbols-outlined" style="font-size: 18px;" aria-hidden="true">repeat</span>
                                <span x-text="t.user.id === ME ? 'Kamu me-repost' : `${t.user.name} me-repost`"></span>
                            </a>
                        </div>
                    </template>

                    <div class="flex items-start justify-between">
                        <a :href="profileUrl(v.user.id)" class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                            <x-avatar name-expr="v.user.name" url-expr="v.user.avatar_url" size="h-10 w-10" />
                            <div class="min-w-0">
                                <div class="break-words font-medium text-ink hover:underline" x-text="v.user.name"></div>
                                <div class="text-xs text-muted" x-text="timeAgo(v.created_at)"></div>
                            </div>
                        </a>

                        @if ($editable ?? false)
                        <div class="relative" x-data="{ open: false }" x-show="t.user.id === ME && t.type !== 'repost'" x-cloak @click.outside="open = false" @keydown.escape.window="open = false">
                            <button type="button" @click="open = !open" class="min-h-[44px] min-w-[44px] rounded-full text-muted hover:bg-ground focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-label="Aksi postingan">
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
                        @endif
                    </div>

                    <!-- Body / edit -->
                    <template x-if="editingId !== t.id">
                        <a :href="`/threads/${v.id}`" class="block mt-3 rounded-xl text-ink whitespace-pre-line break-words focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" x-text="v.body"></a>
                    </template>
                    <template x-if="editingId === t.id">
                        <div class="mt-3">
                            <textarea x-model="editBody" rows="3" maxlength="280"
                                class="block w-full border-line rounded-2xl focus:border-primary focus:ring-primary"></textarea>
                            <div class="mt-2 flex gap-2">
                                <x-primary-button type="button" @click="saveEdit(t)" x-bind:disabled="editBusy">{{ __('Save') }}</x-primary-button>
                                <x-outline-button @click="editingId = null">{{ __('Cancel') }}</x-outline-button>
                            </div>
                        </div>
                    </template>

                    <!-- Images -->
                    <div class="mt-3 grid gap-2 items-start" :class="v.images.length > 1 ? 'grid-cols-2' : 'grid-cols-1'" x-show="v.images.length">
                        <template x-for="(img, i) in v.images" :key="img.id">
                            <button type="button" @click="openImage(v.images, i)" :aria-label="`Buka gambar ${i + 1}`" class="rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"><img :src="img.url" alt="" class="w-full h-auto rounded-2xl cursor-zoom-in"></button>
                        </template>
                    </div>

                    @include('threads._quote-embed', ['expr' => 'v'])

                    <!-- Actions -->
                    <div class="mt-4 flex items-center gap-6 text-sm text-muted">
                        <button type="button" @click="like(v)" :disabled="likeBusy[v.id] === true" :aria-pressed="v.liked_by_me.toString()" aria-label="Sukai postingan" class="inline-flex min-h-[44px] items-center gap-1 rounded-full hover:text-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-60" :class="v.liked_by_me && 'text-red-500'">
                            <span class="material-symbols-outlined" style="font-size: 20px;">favorite</span>
                            <span x-text="v.likes_count"></span>
                        </button>
                        <a :href="`/threads/${v.id}`" aria-label="Buka komentar" class="inline-flex min-h-[44px] items-center gap-1 rounded-full hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                            <span class="material-symbols-outlined" style="font-size: 20px;">chat_bubble</span>
                            <span x-text="v.comments_count"></span>
                        </a>
                        @include('threads._repost-menu', ['expr' => 'v'])
                    </div>
                    <p x-show="actionErrors[v.id]" x-text="actionErrors[v.id]" x-cloak class="mt-2 text-sm text-red-700" role="alert"></p>
                </div>

<x-app-layout>
    @include('threads._api')
    <x-follow-list-modal />

    <script>
        function followRequests() {
            return {
                requests: [],
                busyId: null,

                async init() {
                    try {
                        this.requests = (await api('GET', '/api/follow-requests')).data;
                    } catch (e) {}
                },

                async accept(r) {
                    await this.act(r, () => api('POST', `/api/follow-requests/${r.id}/accept`));
                },

                async reject(r) {
                    await this.act(r, () => api('DELETE', `/api/follow-requests/${r.id}`));
                },

                async act(r, call) {
                    if (this.busyId) return;
                    this.busyId = r.id;
                    try {
                        await call();
                        this.requests = this.requests.filter(x => x.id !== r.id);
                    } catch (e) {}
                    this.busyId = null;
                },
            };
        }
    </script>

    <div class="space-y-4">
        {{-- Ringkasan profil --}}
        <section aria-label="Ringkasan profil" class="flex flex-wrap items-center gap-6 rounded-3xl border border-line bg-white p-6">
            <x-avatar :user="$user" size="h-24 w-24" text="text-3xl" />
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-2xl font-black tracking-tight">{{ $user->name }}</h1>
                <p class="truncate text-muted">{{ $user->email }}</p>
                <div class="mt-2 flex gap-2" x-data>
                    <button type="button" @click="$dispatch('open-follow-list', { userId: {{ $user->id }}, type: 'followers' })"
                            class="inline-flex min-h-[44px] items-baseline gap-1.5 rounded-full px-2 py-2 hover:bg-ground focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        <span class="text-xl font-black">{{ $user->followers_count }}</span>
                        <span class="text-muted">pengikut</span>
                    </button>
                    <button type="button" @click="$dispatch('open-follow-list', { userId: {{ $user->id }}, type: 'following' })"
                            class="inline-flex min-h-[44px] items-baseline gap-1.5 rounded-full px-2 py-2 hover:bg-ground focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        <span class="text-xl font-black">{{ $user->following_count }}</span>
                        <span class="text-muted">mengikuti</span>
                    </button>
                </div>
            </div>
        </section>

        {{-- Permintaan mengikuti (hanya tampil bila ada) --}}
        <section x-data="followRequests()" x-show="requests.length" x-cloak aria-labelledby="requests-title"
                 class="rounded-3xl border border-line bg-white p-6 sm:p-8">
            <h2 id="requests-title" class="text-lg font-bold">Permintaan mengikuti</h2>
            <ul class="mt-4 space-y-3">
                <template x-for="r in requests" :key="r.id">
                    <li class="flex flex-wrap items-center gap-3">
                        <a :href="`/users/${r.id}`" class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                            <x-avatar name-expr="r.name" url-expr="r.avatar_url" size="h-10 w-10" />
                            <span class="min-w-0 flex-1 truncate font-semibold hover:underline" x-text="r.name"></span>
                        </a>
                        <button type="button" @click="accept(r)" :disabled="busyId === r.id"
                                class="inline-flex min-h-[44px] items-center rounded-full bg-primary px-5 font-semibold text-white hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60">Terima</button>
                        <button type="button" @click="reject(r)" :disabled="busyId === r.id"
                                class="inline-flex min-h-[44px] items-center rounded-full border-2 border-ink px-5 font-semibold text-ink hover:bg-ground focus:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 disabled:opacity-60">Tolak</button>
                    </li>
                </template>
            </ul>
        </section>

        <div class="rounded-3xl border border-line bg-white p-6 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-3xl border border-line bg-white p-6 sm:p-8">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-3xl border border-line bg-white p-6 sm:p-8">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>

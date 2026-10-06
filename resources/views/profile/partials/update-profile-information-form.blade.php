<section>
    <header>
        <h2 class="text-lg font-medium text-ink">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-muted">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div x-data="{
            preview: null,
            // Pilih foto → atur (crop 1:1) → hasilnya menggantikan file di input.
            async pickAvatar(e) {
                const input = e.target;
                const file = input.files[0];
                if (! file) { this.preview = null; return; }

                const out = await cropImage(file, { title: 'Atur foto profil', aspect: 1, round: true, ratios: false, original: false });
                if (! out) { input.value = ''; this.preview = null; return; }

                const dt = new DataTransfer();
                dt.items.add(out);
                input.files = dt.files;
                this.preview = URL.createObjectURL(out);
            },
        }">
            <x-input-label for="avatar" value="Foto profil" />
            <div class="mt-2 flex flex-wrap items-center gap-4">
                <span class="relative inline-flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-tint text-2xl font-bold text-primary-dark">
                    <img x-show="preview" x-cloak :src="preview" alt="" class="h-full w-full object-cover">
                    <span x-show="!preview">
                        @if ($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                        @else
                            <span aria-hidden="true">{{ $user->initials }}</span>
                        @endif
                    </span>
                </span>

                <div class="space-y-2">
                    <input id="avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp"
                           @change="pickAvatar($event)"
                           class="block w-full text-sm text-muted file:me-4 file:min-h-[40px] file:cursor-pointer file:rounded-full file:border-0 file:bg-primary-tint file:px-5 file:font-semibold file:text-primary-dark hover:file:bg-line">
                    <p class="text-xs text-muted">JPG, PNG, atau WebP. Maksimal 2 MB.</p>

                    @if ($user->avatar)
                        <label class="inline-flex min-h-[44px] items-center gap-2 text-sm text-muted">
                            <input type="checkbox" name="remove_avatar" value="1" class="h-5 w-5 rounded border-line text-primary focus:ring-primary">
                            Hapus foto profil
                        </label>
                    @endif
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <div class="relative mt-1">
                <span class="material-symbols-outlined absolute inset-y-0 start-0 flex items-center ps-3 text-muted pointer-events-none">person</span>
                <x-text-input id="name" name="name" type="text" class="block w-full ps-11" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <div class="relative mt-1">
                <span class="material-symbols-outlined absolute inset-y-0 start-0 flex items-center ps-3 text-muted pointer-events-none">mail</span>
                <x-text-input id="email" name="email" type="email" class="block w-full ps-11" :value="old('email', $user->email)" required autocomplete="username" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-ink">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-muted hover:text-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus-visible:ring-primary">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-primary-dark">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <input type="hidden" name="is_private" value="0">
            <label for="is_private" class="flex min-h-[44px] cursor-pointer items-start gap-3">
                <input id="is_private" type="checkbox" name="is_private" value="1" @checked(old('is_private', $user->is_private))
                       class="mt-1 h-5 w-5 rounded border-line text-primary focus:ring-primary">
                <span>
                    <span class="block text-sm font-semibold text-ink">Akun privat</span>
                    <span class="block text-sm text-muted">Hanya pengikut yang kamu setujui yang bisa melihat thread-mu. Pengikut yang sudah ada tetap bisa melihat.</span>
                </span>
            </label>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-muted"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

<x-guest-layout heading="Masuk" subheading="Selamat datang kembali. Lanjutkan obrolanmu.">
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Kata sandi" />
                @if (Route::has('password.request'))
                    <a class="rounded-md text-sm font-medium text-primary hover:text-primary-dark hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" href="{{ route('password.request') }}">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>

            <x-password-input id="password" class="mt-1.5"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="inline-flex min-h-[44px] items-center">
            <input id="remember_me" type="checkbox" class="h-5 w-5 rounded border-line text-primary focus:ring-primary" name="remember">
            <span class="ms-2 text-sm text-muted">Ingat saya</span>
        </label>

        <x-primary-button class="w-full">
            Masuk
        </x-primary-button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-muted">
                Belum punya akun?
                <a href="{{ route('register') }}" class="rounded-md font-semibold text-primary hover:text-primary-dark hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Daftar gratis</a>
            </p>
        @endif
    </form>
</x-guest-layout>

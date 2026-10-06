<x-guest-layout heading="Buat akun" subheading="Gabung dan mulai tulis thread pertamamu.">
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input id="name" class="block mt-1.5 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Kata sandi" />

            <x-password-input id="password" class="mt-1.5"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi kata sandi" />

            <x-password-input id="password_confirmation" class="mt-1.5"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            Daftar
        </x-primary-button>

        <p class="text-center text-sm text-muted">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="rounded-md font-semibold text-primary hover:text-primary-dark hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Masuk</a>
        </p>
    </form>
</x-guest-layout>

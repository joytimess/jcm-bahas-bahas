<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bahas-Bahas - Ruang ngobrol dan diskusi</title>
        <meta name="description" content="Bahas-Bahas adalah platform diskusi bergaya media sosial. Buat thread, sisipkan gambar, berkomentar, dan beri like.">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-ground font-roboto text-ink antialiased text-[17px] leading-relaxed">
        @php
            $btn = 'inline-flex min-h-[44px] items-center justify-center rounded-full px-6 py-2.5 font-semibold transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
            $iconBox = 'flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-white';
            $svg = 'h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true';
        @endphp

        <header>
            <div class="mx-auto flex max-w-[1160px] flex-wrap items-center justify-between gap-x-6 gap-y-2 px-6 py-5">
                <a href="/" class="flex items-center gap-3 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg>
                    </span>
                    <span class="font-roboto text-xl font-extrabold">Bahas-Bahas</span>
                </a>

                <nav aria-label="Navigasi utama" class="flex flex-wrap items-center gap-x-1 gap-y-1 text-base font-medium">
                    <a href="#fitur" class="inline-flex min-h-[44px] items-center rounded-full px-4 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Fitur</a>
                    <a href="#cara-kerja" class="inline-flex min-h-[44px] items-center rounded-full px-4 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Cara kerja</a>
                    <a href="#teknologi" class="inline-flex min-h-[44px] items-center rounded-full px-4 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Teknologi</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="{{ $btn }} bg-primary text-white hover:bg-primary-dark focus-visible:ring-primary">Ke Dashboard</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="{{ $btn }} border-2 border-ink hover:bg-ink hover:text-white focus-visible:ring-ink">Masuk</a>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="mx-auto flex max-w-[1160px] flex-wrap items-center gap-12 px-6 py-16 md:py-24">
                <div class="min-w-[300px] flex-1 basis-[460px]">
                    <p class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-4 py-1.5 text-sm font-semibold">
                        <span class="h-2.5 w-2.5 rounded-full bg-primary" aria-hidden="true"></span>
                        Platform diskusi bergaya media sosial
                    </p>
                    <h1 class="mt-6 font-roboto font-extrabold text-[clamp(42px,6.2vw,80px)] leading-[1.02] tracking-[-0.035em]">
                        Bahas apa saja, <span class="bg-secondary px-2 box-decoration-clone">bareng siapa saja.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-xl text-muted">Buat thread, sisipkan gambar, saling berkomentar, dan beri like. Semua obrolan rapi dalam satu tempat.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="{{ $btn }} bg-primary text-white hover:bg-primary-dark focus-visible:ring-primary">Ke Dashboard</a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="{{ $btn }} bg-primary text-white hover:bg-primary-dark focus-visible:ring-primary">Daftar gratis</a>
                            @endif
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="{{ $btn }} border-2 border-ink hover:bg-ink hover:text-white focus-visible:ring-ink">Sudah punya akun</a>
                            @endif
                        @endauth
                    </div>
                </div>

                {{-- Pratinjau: semua teks hanya contoh --}}
                <div class="min-w-[300px] flex-1 basis-[380px]" aria-hidden="true">
                    <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-tint font-bold text-primary-dark">NP</span>
                            <div class="leading-tight">
                                <p class="font-semibold">Nama Pengguna</p>
                                <p class="text-sm text-muted">baru saja</p>
                            </div>
                        </div>
                        <p class="mt-4 font-roboto text-2xl font-bold leading-snug">Judul thread kamu muncul di sini</p>
                        <div class="mt-4 flex h-40 items-center justify-center rounded-2xl bg-primary-tint text-primary">
                            <svg viewBox="0 0 24 24" class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-9 9"/></svg>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <span class="rounded-full bg-primary px-4 py-1.5 text-sm font-semibold text-white">Suka</span>
                            <span class="rounded-full bg-primary-tint px-4 py-1.5 text-sm font-semibold text-primary-dark">Komentar</span>
                        </div>
                        <div class="mt-4 rounded-2xl bg-primary-dark px-4 py-3 text-sm text-white">Setuju banget, ini topik yang seru dibahas!</div>
                    </div>
                </div>
            </section>

            {{-- Fitur --}}
            <section id="fitur" class="scroll-mt-4 bg-white py-16 md:py-24">
                <div class="mx-auto max-w-[1160px] px-6">
                    <p class="text-sm font-bold uppercase tracking-widest text-primary">Fitur utama</p>
                    <h2 class="mt-3 max-w-2xl font-roboto font-extrabold text-[clamp(34px,4.4vw,56px)] leading-[1.08] tracking-tight">Semua yang kamu butuhkan untuk berdiskusi.</h2>

                    <div class="mt-12 grid gap-6" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr))">
                        <article class="rounded-3xl bg-ground p-8">
                            <span class="{{ $iconBox }}"><svg viewBox="0 0 24 24" class="{!! $svg !!}"><path d="M4 5h16M4 10h16M4 15h10"/><path d="M4 20h6"/></svg></span>
                            <h3 class="mt-5 font-roboto text-2xl font-bold">Thread</h3>
                            <p class="mt-2 text-muted">Tulis postingan berisi teks dan gambar. Lihat, ubah, atau hapus kapan saja.</p>
                        </article>
                        <article class="rounded-3xl bg-ground p-8">
                            <span class="{{ $iconBox }}"><svg viewBox="0 0 24 24" class="{!! $svg !!}"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg></span>
                            <h3 class="mt-5 font-roboto text-2xl font-bold">Komentar</h3>
                            <p class="mt-2 text-muted">Tanggapi setiap thread lewat komentar. Komentarmu bisa diubah atau dihapus.</p>
                        </article>
                        <article class="rounded-3xl bg-ground p-8">
                            <span class="{{ $iconBox }}"><svg viewBox="0 0 24 24" class="{!! $svg !!}"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.600-7 10-7 10Z"/></svg></span>
                            <h3 class="mt-5 font-roboto text-2xl font-bold">Like</h3>
                            <p class="mt-2 text-muted">Beri like pada thread maupun komentar untuk menunjukkan dukungan.</p>
                        </article>
                        <article class="rounded-3xl bg-ground p-8">
                            <span class="{{ $iconBox }}"><svg viewBox="0 0 24 24" class="{!! $svg !!}"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-9 9"/></svg></span>
                            <h3 class="mt-5 font-roboto text-2xl font-bold">Lampiran gambar</h3>
                            <p class="mt-2 text-muted">Sisipkan gambar di thread dan di komentar supaya diskusi lebih jelas.</p>
                        </article>
                        <article class="rounded-3xl bg-ground p-8">
                            <span class="{{ $iconBox }}"><svg viewBox="0 0 24 24" class="{!! $svg !!}"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg></span>
                            <h3 class="mt-5 font-roboto text-2xl font-bold">Akun aman</h3>
                            <p class="mt-2 text-muted">Daftar, masuk, keluar, reset password, dan verifikasi email sudah tersedia.</p>
                        </article>
                        <article class="rounded-3xl bg-ground p-8">
                            <span class="{{ $iconBox }}"><svg viewBox="0 0 24 24" class="{!! $svg !!}"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg></span>
                            <h3 class="mt-5 font-roboto text-2xl font-bold">Profil pribadi</h3>
                            <p class="mt-2 text-muted">Ubah data profil, ganti password, atau hapus akun kapan pun kamu mau.</p>
                        </article>
                    </div>
                </div>
            </section>

            {{-- Cara kerja --}}
            <section id="cara-kerja" class="scroll-mt-4 mx-auto max-w-[1160px] px-6 py-16 md:py-24">
                <p class="text-sm font-bold uppercase tracking-widest text-primary">Cara kerja</p>
                <h2 class="mt-3 max-w-2xl font-roboto font-extrabold text-[clamp(34px,4.4vw,56px)] leading-[1.08] tracking-tight">Mulai ngobrol dalam tiga langkah.</h2>
                <ol class="mt-12 grid gap-8" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr))">
                    <li class="border-t-4 border-ink pt-6">
                        <span class="font-roboto text-6xl font-extrabold text-primary" aria-hidden="true">1</span>
                        <h3 class="mt-3 font-roboto text-2xl font-bold">Buat akun</h3>
                        <p class="mt-2 text-muted">Daftar dengan email, lalu verifikasi supaya akunmu siap dipakai.</p>
                    </li>
                    <li class="border-t-4 border-ink pt-6">
                        <span class="font-roboto text-6xl font-extrabold text-primary" aria-hidden="true">2</span>
                        <h3 class="mt-3 font-roboto text-2xl font-bold">Tulis thread</h3>
                        <p class="mt-2 text-muted">Buka dashboard, tulis topikmu, dan tambahkan gambar bila perlu.</p>
                    </li>
                    <li class="border-t-4 border-ink pt-6">
                        <span class="font-roboto text-6xl font-extrabold text-primary" aria-hidden="true">3</span>
                        <h3 class="mt-3 font-roboto text-2xl font-bold">Berdiskusi</h3>
                        <p class="mt-2 text-muted">Baca thread orang lain, beri komentar, dan tekan like pada yang kamu suka.</p>
                    </li>
                </ol>
            </section>

            {{-- Soft delete --}}
            <section class="mx-auto max-w-[1160px] px-6 pb-16 md:pb-24">
                <div class="flex flex-wrap items-center gap-12 rounded-[32px] bg-primary-dark p-8 text-white md:p-16">
                    <div class="min-w-[280px] flex-1 basis-[420px]">
                        <p class="text-sm font-bold uppercase tracking-widest text-secondary">Data lebih terjaga</p>
                        <h2 class="mt-3 font-roboto font-extrabold text-[clamp(34px,4.4vw,56px)] leading-[1.08] tracking-tight">Dihapus tidak berarti hilang begitu saja.</h2>
                        <p class="mt-5 text-lg">Data yang dihapus hanya ditandai, bukan dibuang dari database. Saat sebuah thread dihapus, komentar dan gambarnya ikut ditandai agar tetap konsisten.</p>
                    </div>
                    <ul class="min-w-[260px] flex-1 basis-[320px] space-y-3">
                        <li class="flex items-center gap-3 rounded-2xl border border-white/20 px-5 py-4 font-semibold">
                            <span class="h-3 w-3 rounded-full bg-secondary" aria-hidden="true"></span>Thread
                        </li>
                        <li class="ml-6 flex items-center gap-3 rounded-2xl border border-white/20 px-5 py-4">
                            <span class="h-3 w-3 rounded-full bg-secondary" aria-hidden="true"></span>Komentar ikut ditandai
                        </li>
                        <li class="ml-12 flex items-center gap-3 rounded-2xl border border-white/20 px-5 py-4">
                            <span class="h-3 w-3 rounded-full bg-secondary" aria-hidden="true"></span>Gambar ikut ditandai
                        </li>
                    </ul>
                </div>
            </section>

            {{-- Teknologi --}}
            <section id="teknologi" class="scroll-mt-4 mx-auto max-w-[1160px] px-6 pb-16 md:pb-24">
                <p class="text-sm font-bold uppercase tracking-widest text-primary">Teknologi</p>
                <h2 class="mt-3 max-w-2xl font-roboto font-extrabold text-[clamp(34px,4.4vw,56px)] leading-[1.08] tracking-tight">Dibangun di atas fondasi yang matang.</h2>
                <p class="mt-4 max-w-2xl text-lg text-muted">Halaman web memuat data lewat REST API yang dilindungi autentikasi berbasis token.</p>
                <ul class="mt-8 flex flex-wrap gap-3">
                    @foreach (['Laravel 13', 'PHP 8.3+', 'Laravel Sanctum', 'Laravel Breeze', 'Pest', 'Pint'] as $tech)
                        <li class="rounded-full border border-line bg-white px-5 py-2 font-semibold">{{ $tech }}</li>
                    @endforeach
                </ul>
            </section>

            {{-- CTA --}}
            <section class="mx-auto max-w-[1160px] px-6 pb-16 md:pb-24">
                <div class="rounded-[32px] bg-primary p-8 text-center text-white md:p-16">
                    <h2 class="mx-auto max-w-2xl font-roboto font-extrabold text-[clamp(34px,4.4vw,56px)] leading-[1.08] tracking-tight">Ada yang mau dibahas? Mulai dari sini.</h2>
                    <p class="mt-4 text-lg">Buat akun dalam hitungan menit dan tulis thread pertamamu.</p>
                    <div class="mt-8">
                        @auth
                            <a href="{{ route('dashboard') }}" class="{{ $btn }} bg-secondary text-ink hover:brightness-95 focus-visible:ring-white focus-visible:ring-offset-primary">Ke Dashboard</a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="{{ $btn }} bg-secondary text-ink hover:brightness-95 focus-visible:ring-white focus-visible:ring-offset-primary">Daftar sekarang</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-line">
            <div class="mx-auto flex max-w-[1160px] flex-wrap items-center justify-between gap-4 px-6 py-8 text-base">
                <span class="font-roboto text-lg font-extrabold">Bahas-Bahas</span>
                <span class="text-muted">Dibuat dengan Laravel</span>
                @guest
                    <nav aria-label="Navigasi footer" class="flex gap-2">
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-flex min-h-[44px] items-center rounded-full px-4 font-medium hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Masuk</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex min-h-[44px] items-center rounded-full px-4 font-medium hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Daftar</a>
                        @endif
                    </nav>
                @endguest
            </div>
        </footer>
    </body>
</html>

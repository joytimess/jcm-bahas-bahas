<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $attributes->get('heading') ? $attributes->get('heading').' - ' : '' }}Bahas-Bahas</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-ground font-roboto text-ink antialiased">
        <div class="grid min-h-screen lg:grid-cols-2">
            {{-- Panel brand (desktop) --}}
            <aside class="relative hidden flex-col justify-between overflow-hidden bg-primary-dark p-12 text-white lg:flex">
                <a href="/" class="flex w-fit items-center gap-3 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg>
                    </span>
                    <span class="text-xl font-black">Bahas-Bahas</span>
                </a>

                <div>
                    <p class="text-sm font-bold uppercase tracking-widest text-secondary">Ruang ngobrol dan diskusi</p>
                    <p class="mt-4 text-5xl font-black leading-[1.05] tracking-[-0.03em]">Bahas apa saja, bareng siapa saja.</p>
                    <ul class="mt-8 space-y-3 text-lg">
                        <li class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-secondary" aria-hidden="true"></span>Buat thread dengan teks dan gambar</li>
                        <li class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-secondary" aria-hidden="true"></span>Saling berkomentar</li>
                        <li class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-secondary" aria-hidden="true"></span>Beri like pada yang kamu suka</li>
                    </ul>
                </div>

                <p class="text-sm text-white/80">Dibuat dengan Laravel</p>

                <span class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-primary/40" aria-hidden="true"></span>
            </aside>

            {{-- Form --}}
            <main class="flex flex-col items-center justify-center px-6 py-10">
                <a href="/" class="mb-8 flex items-center gap-3 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary lg:hidden">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg>
                    </span>
                    <span class="text-xl font-black">Bahas-Bahas</span>
                </a>

                <div class="w-full max-w-md rounded-3xl border border-line bg-white p-8 shadow-sm sm:p-10">
                    @if ($attributes->get('heading'))
                        <h1 class="text-3xl font-black tracking-tight">{{ $attributes->get('heading') }}</h1>
                        @if ($attributes->get('subheading'))
                            <p class="mt-2 text-muted">{{ $attributes->get('subheading') }}</p>
                        @endif
                        <div class="mt-8">{{ $slot }}</div>
                    @else
                        {{ $slot }}
                    @endif
                </div>

                <a href="/" class="mt-6 inline-flex min-h-[44px] items-center rounded-full px-4 text-sm font-medium text-muted hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">&larr; Kembali ke beranda</a>
            </main>
        </div>
    </body>
</html>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Bahas-Bahas</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

        <!-- Google Material Symbols (filled) -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
        <style>
            [x-cloak] { display: none !important; }
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-ground font-roboto text-ink antialiased">
        <div {{ $attributes->merge(['class' => 'mx-auto grid min-h-screen max-w-[1400px] gap-6 px-4 lg:grid-cols-4 lg:px-6']) }}>
            <!-- Navigation (1/4, floating) -->
            @include('layouts.navigation')

            <!-- Page Content (1/2, atau 3/4 bila tanpa kolom kanan) -->
            <main class="min-w-0 pb-24 pt-6 lg:pb-6 {{ isset($aside) ? 'lg:col-span-2' : 'lg:col-span-3' }}">
                {{ $slot }}
            </main>

            <!-- Aside (1/4) -->
            @isset($aside)
                <aside class="sticky top-0 hidden h-screen py-6 lg:block">
                    {{ $aside }}
                </aside>
            @endisset
        </div>

        <x-image-cropper />
    </body>
</html>

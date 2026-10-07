@php
    $user = Auth::user();
    $links = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'active' => request()->routeIs('dashboard', 'threads.*')],
        ['route' => 'search', 'label' => 'Pencarian', 'icon' => 'search', 'active' => request()->routeIs('search')],
        ['route' => 'my.threads', 'label' => 'My Threads', 'icon' => 'article', 'active' => request()->routeIs('my.threads')],
        ['route' => 'my.likes', 'label' => 'My Likes', 'icon' => 'favorite', 'active' => request()->routeIs('my.likes')],
        ['route' => 'profile.edit', 'label' => 'Profile', 'icon' => 'person', 'active' => request()->routeIs('profile.edit')],
    ];
    $focus = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-primary';
@endphp

{{-- Sidebar kiri (desktop) --}}
<aside class="sticky top-0 hidden h-screen py-6 lg:block">
    <nav aria-label="Navigasi utama" class="flex h-full flex-col rounded-3xl border border-line bg-white p-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl p-2 {{ $focus }}">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg>
            </span>
            <span class="text-xl font-black">Bahas-Bahas</span>
        </a>

        <ul class="mt-6 space-y-1">
            @foreach ($links as $link)
                <li>
                    <a href="{{ route($link['route']) }}" @if ($link['active']) aria-current="page" @endif
                       class="flex min-h-[48px] items-center gap-3 rounded-2xl px-4 font-semibold transition-colors {{ $focus }}
                              {{ $link['active'] ? 'bg-primary-tint text-primary-dark' : 'text-muted hover:bg-ground hover:text-ink' }}">
                        <span class="material-symbols-outlined" aria-hidden="true">{{ $link['icon'] }}</span>
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Akun + logout (paling bawah) --}}
        <div class="relative mt-auto" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <div x-show="open" x-cloak x-transition.origin.bottom
                 class="absolute inset-x-0 bottom-full mb-2 rounded-2xl border border-line bg-white p-1 shadow-lg">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex min-h-[44px] w-full items-center gap-3 rounded-xl px-3 text-start font-semibold text-red-700 hover:bg-ground {{ $focus }}">
                        <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                        Log Out
                    </button>
                </form>
            </div>

            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="menu"
                    class="flex w-full items-center gap-3 rounded-2xl p-2 text-start hover:bg-ground {{ $focus }}">
                <x-avatar :user="$user" size="h-10 w-10" />
                <span class="min-w-0 flex-1 truncate font-semibold">{{ $user->name }}</span>
                <span class="material-symbols-outlined text-muted transition-transform" :class="open && 'rotate-180'" aria-hidden="true">expand_less</span>
            </button>
        </div>
    </nav>
</aside>

{{-- Bottom bar (ponsel / tablet) --}}
<nav aria-label="Navigasi utama" class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white lg:hidden">
    <div class="mx-auto flex max-w-xl items-center justify-around px-2 py-1">
        <a href="{{ route('dashboard') }}" aria-label="Bahas-Bahas" class="hidden sm:flex min-h-[48px] min-w-[48px] items-center justify-center rounded-2xl {{ $focus }}">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-white">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg>
            </span>
        </a>

        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}" @if ($link['active']) aria-current="page" @endif
               class="flex min-h-[48px] min-w-0 flex-1 flex-col items-center justify-center rounded-2xl px-0.5 text-[10px] sm:text-[11px] whitespace-nowrap font-semibold {{ $focus }}
                      {{ $link['active'] ? 'text-primary' : 'text-muted' }}">
                <span class="material-symbols-outlined" aria-hidden="true">{{ $link['icon'] }}</span>
                {{ $link['label'] }}
            </a>
        @endforeach

        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <div x-show="open" x-cloak x-transition.origin.bottom.right
                 class="absolute bottom-full end-0 mb-2 w-44 rounded-2xl border border-line bg-white p-1 shadow-lg">
                <p class="truncate px-3 py-2 text-sm font-semibold">{{ $user->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex min-h-[44px] w-full items-center gap-3 rounded-xl px-3 text-start font-semibold text-red-700 hover:bg-ground {{ $focus }}">
                        <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                        Log Out
                    </button>
                </form>
            </div>

            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="menu" aria-label="Menu akun"
                    class="flex min-h-[48px] min-w-[48px] items-center justify-center rounded-2xl {{ $focus }}">
                <x-avatar :user="$user" size="h-9 w-9" />
            </button>
        </div>
    </div>
</nav>

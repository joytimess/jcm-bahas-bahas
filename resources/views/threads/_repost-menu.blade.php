{{-- Tombol repost: menu Repost / Batal repost + Quote. $expr = ekspresi thread asli; $quote = boleh quote. --}}
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" :disabled="!{{ $expr }}.can_repost || repostBusy[{{ $expr }}.id] === true" :aria-pressed="{{ $expr }}.reposted_by_me.toString()" :title="{{ $expr }}.can_repost ? 'Repost' : 'Thread dari akun privat tidak bisa dibagikan'" aria-label="Repost atau quote" class="inline-flex min-h-[44px] items-center gap-1 rounded-full hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-50" :class="{{ $expr }}.reposted_by_me && 'text-primary'">
        <span class="material-symbols-outlined" style="font-size: 20px;">repeat</span>
        <span x-text="{{ $expr }}.reposts_count + ({{ $expr }}.quotes_count || 0)"></span>
    </button>
    <div x-show="open" x-cloak x-transition.origin.top.left class="absolute start-0 z-20 mt-1 w-44 rounded-2xl border border-line bg-white p-1 shadow-lg">
        <button type="button" @click="open = false; repost({{ $expr }})" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-ink hover:bg-ground">
            <span class="material-symbols-outlined" style="font-size: 18px;">repeat</span>
            <span x-text="{{ $expr }}.reposted_by_me ? 'Batalkan repost' : 'Repost'"></span>
        </button>
        @if ($quote ?? true)
        <button type="button" @click="open = false; startQuote({{ $expr }})" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-ink hover:bg-ground">
            <span class="material-symbols-outlined" style="font-size: 18px;">format_quote</span>
            Quote
        </button>
        @endif
    </div>
</div>

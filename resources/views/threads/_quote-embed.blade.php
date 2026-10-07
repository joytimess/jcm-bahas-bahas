{{-- Kartu kecil thread yang di-quote. $expr = ekspresi Alpine thread pemilik quote. --}}
<template x-if="{{ $expr }}.type === 'quote'">
    <div class="mt-3">
        <a x-show="{{ $expr }}.repost_of" :href="{{ $expr }}.repost_of && `/threads/${ {{ $expr }}.repost_of.id }`" class="block rounded-2xl border border-line bg-ground p-4 hover:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
            <div class="flex items-center gap-2 text-sm">
                <span class="font-semibold text-ink" x-text="{{ $expr }}.repost_of?.user.name"></span>
                <span class="text-xs text-muted" x-text="{{ $expr }}.repost_of && timeAgo({{ $expr }}.repost_of.created_at)"></span>
            </div>
            <p class="mt-1 line-clamp-4 whitespace-pre-line break-words text-sm text-ink" x-text="{{ $expr }}.repost_of?.body"></p>
            <img x-show="{{ $expr }}.repost_of?.images.length" :src="{{ $expr }}.repost_of?.images[0]?.url" alt="" class="mt-2 max-h-48 rounded-xl object-cover">
        </a>
        <p x-show="{{ $expr }}.repost_unavailable" class="rounded-2xl border border-line bg-ground p-4 text-sm text-muted">Thread tidak tersedia.</p>
    </div>
</template>

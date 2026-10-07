{{-- Dialog crop gambar global. Dipakai lewat window.cropImage / window.cropImages (resources/js/image-cropper.js). --}}
<div x-data="imageCropper" x-show="open" x-cloak
     @keydown.escape.window="cancel()"
     class="fixed inset-0 z-50 flex items-end justify-center bg-ink/70 p-0 sm:items-center sm:p-6"
     role="dialog" aria-modal="true" aria-labelledby="cropper-title">
    <div class="flex max-h-[100dvh] w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl bg-white sm:rounded-3xl"
         :class="round && 'cropper-round'">
        <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-4">
            <div class="min-w-0">
                <h2 id="cropper-title" class="truncate text-lg font-bold" x-text="title"></h2>
                <p class="text-sm text-muted" x-show="position" x-text="position"></p>
            </div>
            <button type="button" @click="cancel()" aria-label="Tutup"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-muted hover:bg-ground hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        {{-- Area crop --}}
        <div class="relative min-h-0 flex-1 bg-ground">
            <div class="h-[55vh] max-h-[520px] w-full">
                <img x-ref="image" :src="src" @load="setup()" alt="Gambar yang akan dipotong" class="block max-w-full">
            </div>
            <div x-show="busy" class="absolute inset-0 flex items-center justify-center bg-ground/80"><x-spinner /></div>
        </div>

        {{-- Pengaturan --}}
        <div class="space-y-3 border-t border-line px-5 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <template x-if="showRatios">
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Rasio">
                        <template x-for="r in ratios" :key="r.label">
                            <button type="button" @click="setRatio(r.value)" x-text="r.label"
                                    :aria-pressed="(Object.is(current, r.value) || current === r.value).toString()"
                                    class="inline-flex min-h-[40px] items-center rounded-full border px-4 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                    :class="(Object.is(current, r.value) || current === r.value) ? 'border-primary bg-primary-tint text-primary-dark' : 'border-line text-muted hover:text-ink'"></button>
                        </template>
                    </div>
                </template>

                <div class="ms-auto flex gap-1">
                    <button type="button" @click="rotate(-90)" aria-label="Putar ke kiri"
                            class="flex h-11 w-11 items-center justify-center rounded-full text-muted hover:bg-ground hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        <span class="material-symbols-outlined" aria-hidden="true">rotate_left</span>
                    </button>
                    <button type="button" @click="rotate(90)" aria-label="Putar ke kanan"
                            class="flex h-11 w-11 items-center justify-center rounded-full text-muted hover:bg-ground hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        <span class="material-symbols-outlined" aria-hidden="true">rotate_right</span>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <button type="button" @click="cancel()"
                        class="inline-flex min-h-[44px] items-center rounded-full border-2 border-ink px-5 font-semibold text-ink hover:bg-ground focus:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">Batal</button>
                <button type="button" x-show="allowOriginal" @click="useOriginal()"
                        class="inline-flex min-h-[44px] items-center rounded-full px-5 font-semibold text-muted hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Pakai asli</button>
                <button type="button" x-ref="confirm" @click="confirm()" :disabled="busy"
                        class="inline-flex min-h-[44px] items-center rounded-full bg-primary px-6 font-semibold text-white hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60">Pakai gambar</button>
            </div>
        </div>
    </div>
</div>

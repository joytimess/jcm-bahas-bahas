@props(['id' => 'search-query', 'shortcut' => false])

<form @if ($shortcut) x-data="searchShortcut()" @endif @submit.prevent="submit()" role="search"
      class="rounded-3xl border border-line bg-white p-5">
    <label for="{{ $id }}" class="mb-3 block font-semibold text-ink">Cari postingan atau pengguna</label>
    <div class="relative">
        <span class="material-symbols-outlined pointer-events-none absolute inset-y-0 start-3 flex items-center text-muted" aria-hidden="true">search</span>
        <x-text-input :id="$id" type="search" maxlength="100" x-model="{{ $shortcut ? 'query' : 'draftQuery' }}"
                      placeholder="Ketik kata pencarian..." class="w-full min-w-0 ps-10" aria-describedby="{{ $id }}-error" />
    </div>
    <p id="{{ $id }}-error" x-show="{{ $shortcut ? 'error' : 'validationError' }}"
       x-text="{{ $shortcut ? 'error' : 'validationError' }}" x-cloak role="alert" class="mt-2 text-sm text-red-700"></p>
</form>

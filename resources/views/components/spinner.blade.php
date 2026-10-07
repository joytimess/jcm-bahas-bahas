@props(['size' => 'h-8 w-8'])

<span role="status" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center']) }}>
    <span class="{{ $size }} animate-spin rounded-full border-4 border-primary-tint border-t-primary"></span>
    <span class="sr-only">Memuat...</span>
</span>

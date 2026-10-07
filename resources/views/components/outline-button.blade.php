<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex min-h-[44px] items-center justify-center rounded-full border-2 border-ink px-4 py-2 text-sm font-semibold text-ink hover:bg-ground transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60']) }}>
    {{ $slot }}
</button>

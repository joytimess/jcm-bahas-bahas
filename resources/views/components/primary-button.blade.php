<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-[44px] items-center justify-center rounded-full border border-transparent bg-primary px-6 py-2.5 font-semibold text-white transition-colors duration-150 hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2']) }}>
    {{ $slot }}
</button>

@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-line bg-white px-4 py-2.5 text-ink shadow-sm placeholder:text-muted/60 focus:border-primary focus:ring-primary disabled:bg-ground']) }}>

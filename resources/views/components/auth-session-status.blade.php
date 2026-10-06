@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl bg-primary-tint px-4 py-3 text-sm font-medium text-primary-dark']) }}>
        {{ $status }}
    </div>
@endif

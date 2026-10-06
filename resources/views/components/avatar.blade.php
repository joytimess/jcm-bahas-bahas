{{--
    Avatar bulat: foto bila ada, inisial bila belum.
    - Server-side : <x-avatar :user="$user" size="h-10 w-10" />
    - Dalam Alpine: <x-avatar name-expr="t.user.name" url-expr="t.user.avatar_url" size="h-10 w-10" />
--}}
@props(['user' => null, 'size' => 'h-10 w-10', 'text' => 'text-sm', 'nameExpr' => null, 'urlExpr' => null])

@php
    $wrap = "relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-tint font-bold text-primary-dark $size $text";
@endphp

@if ($user)
    <span {{ $attributes->merge(['class' => $wrap]) }}>
        @if ($user->avatar_url)
            <img src="{{ $user->avatar_url }}" alt="" class="h-full w-full object-cover">
        @else
            <span aria-hidden="true">{{ $user->initials }}</span>
        @endif
    </span>
@else
    <span {{ $attributes->merge(['class' => $wrap]) }}>
        <img x-show="{{ $urlExpr }}" x-bind:src="{{ $urlExpr }}" alt="" class="h-full w-full object-cover">
        <span x-show="! {{ $urlExpr }}" x-text="initials({{ $nameExpr }})" aria-hidden="true"></span>
    </span>
@endif

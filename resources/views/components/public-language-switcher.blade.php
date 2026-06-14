@props([
    'locale' => 'en',
    'accent' => [],
])

@php
    $locales = [
        'en' => 'English',
        'hi' => 'हिंदी',
    ];
    $primary = $accent['primary'] ?? '#f59e0b';
@endphp

<div
    class="flex items-center rounded-lg border border-slate-200 bg-white p-0.5 text-xs font-semibold shadow-sm"
    role="group"
    aria-label="{{ __('welcome.nav.language') }}"
>
    @foreach ($locales as $code => $label)
        @if ($locale === $code)
            <span
                class="rounded-md px-2.5 py-1.5 text-white"
                style="background-color: {{ $primary }}"
            >{{ $label }}</span>
        @else
            <a
                href="{{ route('public.locale', ['locale' => $code]) }}"
                class="rounded-md px-2.5 py-1.5 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                hreflang="{{ $code }}"
            >{{ $label }}</a>
        @endif
    @endforeach
</div>

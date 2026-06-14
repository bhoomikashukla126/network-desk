@php
    $welcome = $welcome ?? config('welcome');
    $activePage = $activePage ?? 'welcome';
    $locale = $welcome['locale'] ?? app()->getLocale();
    $nav = $welcome['nav'] ?? [];
@endphp
@once
    <style>
        .public-header {
            position: sticky; top: 0; z-index: 50;
            border-bottom: 1px solid rgba(226,232,240,0.65);
            background: rgba(255,255,255,0.78);
            backdrop-filter: blur(10px);
        }
        .public-header-inner {
            display: flex; align-items: center; justify-content: space-between;
            gap: 0.75rem; min-height: 3.5rem;
            padding: 0.65rem 1rem;
        }
        @media (min-width: 640px) {
            .public-header-inner { min-height: 4rem; padding: 1rem 1.5rem; }
        }
        @media (min-width: 1024px) {
            .public-header-inner { padding: 1.25rem 2rem; }
        }
        .public-brand-name { display: none; }
        @media (min-width: 420px) {
            .public-brand-name { display: inline; }
        }
        .public-header-actions {
            display: flex; align-items: center; flex-shrink: 0;
            gap: 0.35rem;
            padding: 0.2rem; border-radius: 0.85rem;
            background: rgba(255,255,255,0.65);
            border: 1px solid rgba(226,232,240,0.9);
            box-shadow: 0 1px 3px rgba(15,23,42,0.04);
        }
        @media (min-width: 640px) {
            .public-header-actions {
                gap: 0.5rem; padding: 0; border: none; background: transparent; box-shadow: none;
            }
        }
        .public-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            border-radius: 0.65rem; font-size: 0.8125rem; font-weight: 600; line-height: 1;
            white-space: nowrap; transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
        }
        @media (min-width: 640px) {
            .public-btn { border-radius: 0.75rem; font-size: 0.875rem; gap: 0.45rem; }
        }
        .public-btn svg { flex-shrink: 0; }
        .public-btn--ghost {
            padding: 0.55rem 0.65rem; color: #475569; background: transparent;
        }
        .public-btn--ghost:hover { color: #0f172a; background: #f8fafc; }
        @media (min-width: 640px) {
            .public-btn--ghost {
                padding: 0.625rem 1rem; border: 1px solid #e2e8f0;
                background: rgba(255,255,255,0.9); box-shadow: 0 1px 2px rgba(15,23,42,0.04);
            }
            .public-btn--ghost:hover { background: #fff; }
        }
        .public-btn--ghost.is-active {
            color: {{ $welcome['accent']['primary_dark'] ?? '#d97706' }};
            background: {{ $welcome['accent']['badge_bg'] ?? 'rgba(245,158,11,0.14)' }};
        }
        @media (min-width: 640px) {
            .public-btn--ghost.is-active { border-color: {{ $welcome['accent']['primary'] ?? '#f59e0b' }}33; }
        }
        .public-btn-label-short { display: inline; }
        .public-btn-label-full { display: none; }
        @media (min-width: 640px) {
            .public-btn-label-short { display: none; }
            .public-btn-label-full { display: inline; }
        }
        .public-lang-switcher { flex-shrink: 0; }
        .public-feature-icon {
            background-color: {{ $welcome['accent']['badge_bg'] ?? 'rgba(245,158,11,0.14)' }};
            color: {{ $welcome['accent']['primary'] ?? '#f59e0b' }};
        }
    </style>
@endonce
<header class="public-header mx-auto w-full max-w-6xl">
    <div class="public-header-inner sm:px-6 lg:px-8">
        <a href="{{ route('welcome') }}" class="flex min-w-0 items-center gap-2.5 transition hover:opacity-80 sm:gap-3">
            @if (! empty($welcome['logo']))
                <img src="{{ asset($welcome['logo']) }}" alt="" class="h-9 w-9 shrink-0 rounded-xl object-contain shadow-sm sm:h-10 sm:w-10">
            @else
                <div class="public-feature-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-base font-semibold sm:h-10 sm:w-10 sm:text-lg">{{ $welcome['icon'] ?? '◆' }}</div>
            @endif
            <span class="public-brand-name truncate text-base font-semibold tracking-tight text-slate-900 sm:text-lg">{{ config('app.name') }}</span>
        </a>
        <div class="public-header-actions">
            @if ($activePage === 'docs')
                <a href="{{ route('welcome') }}" class="public-btn public-btn--ghost" aria-label="{{ $welcome['home_label'] ?? 'Home' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5z"/></svg>
                    <span>{{ $welcome['home_label'] ?? 'Home' }}</span>
                </a>
            @endif
            <a href="{{ route('docs') }}" class="public-btn public-btn--ghost{{ $activePage === 'docs' ? ' is-active' : '' }}" @if ($activePage === 'docs') aria-current="page" @endif aria-label="{{ $welcome['docs_label'] ?? 'Documentation' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8M8 11h6"/></svg>
                <span class="public-btn-label-short">{{ $nav['docs_short'] ?? 'Docs' }}</span>
                <span class="public-btn-label-full">{{ $welcome['docs_label'] ?? 'Documentation' }}</span>
            </a>
            <div class="public-lang-switcher">
                <x-public-language-switcher :locale="$locale" :accent="$welcome['accent'] ?? []" />
            </div>
        </div>
    </div>
</header>

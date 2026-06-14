@php
    $welcome = $welcome ?? config('welcome');
    $docs = $docs ?? config('documentation');
    $docUi = $docs['ui'] ?? [];
    $accent = $welcome['accent'] ?? [];
    $sections = $docs['sections'] ?? [];
    $sectionIcons = [
        'overview' => '📋',
        'getting-started' => '🚀',
        'complaints' => '📋',
        'create-edit' => '✏️',
        'import' => '📥',
        'export' => '📤',
        'access' => '🔐',
        'profile' => '⚙️',
        'languages-themes' => '🌐',
        'permissions' => '🔑',
        'troubleshooting' => '💡',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $docs['subtitle'] ?? 'Documentation' }}">
    <title>{{ $docs['title'] ?? 'Documentation' }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --docs-primary: {{ $accent['primary'] ?? '#10b981' }};
            --docs-primary-dark: {{ $accent['primary_dark'] ?? '#059669' }};
            --docs-badge-bg: {{ $accent['badge_bg'] ?? 'rgba(16,185,129,0.12)' }};
            --docs-glow: {{ $accent['glow'] ?? 'rgba(16,185,129,0.18)' }};
            --docs-glow-secondary: {{ $accent['glow_secondary'] ?? 'rgba(5,150,105,0.12)' }};
            --docs-gradient-from: {{ $accent['gradient_from'] ?? '#ecfdf5' }};
            --docs-gradient-to: {{ $accent['gradient_to'] ?? '#ffffff' }};
            --docs-header-h: 3.5rem;
        }
        @media (min-width: 640px) {
            :root { --docs-header-h: 4rem; }
        }

        .docs-gradient {
            background:
                radial-gradient(ellipse 80% 60% at 10% 0%, var(--docs-glow), transparent 55%),
                radial-gradient(ellipse 70% 50% at 90% 10%, var(--docs-glow-secondary), transparent 50%),
                linear-gradient(180deg, var(--docs-gradient-from) 0%, var(--docs-gradient-to) 100%);
        }

        .docs-primary { background-color: var(--docs-primary); }
        .docs-primary:hover { background-color: var(--docs-primary-dark); }
        .docs-badge {
            background-color: var(--docs-badge-bg);
            color: var(--docs-primary-dark);
        }

        .docs-progress {
            position: fixed; top: 0; left: 0; right: 0; z-index: 60; height: 3px;
            background: rgba(148,163,184,0.2);
        }
        .docs-progress-bar {
            height: 100%; width: 0%;
            background: linear-gradient(90deg, var(--docs-primary), var(--docs-primary-dark));
            transition: width 0.1s linear;
        }

        .docs-hero {
            position: relative; overflow: hidden;
            border-radius: 1.25rem;
            border: 1px solid rgba(255,255,255,0.9);
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.75) 100%);
            box-shadow: 0 4px 24px rgba(15,23,42,0.06), 0 0 0 1px rgba(148,163,184,0.08);
            padding: 1.5rem;
        }
        @media (min-width: 640px) { .docs-hero { padding: 2rem 2.25rem; } }
        .docs-hero-glow {
            position: absolute; top: -40%; right: -10%; width: 55%; height: 140%;
            background: radial-gradient(circle, var(--docs-glow), transparent 70%);
            pointer-events: none;
        }
        .docs-hero-meta {
            display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1.25rem;
        }
        .docs-hero-chip {
            display: inline-flex; align-items: center; gap: 0.35rem;
            border-radius: 9999px; border: 1px solid #e2e8f0; background: #fff;
            padding: 0.35rem 0.75rem; font-size: 0.75rem; font-weight: 500; color: #64748b;
        }
        .docs-back-home {
            display: inline-flex; align-items: center; gap: 0.35rem;
            margin-bottom: 0.85rem; font-size: 0.8125rem; font-weight: 600; color: #64748b;
            transition: color 0.15s ease;
        }
        .docs-back-home:hover { color: var(--docs-primary-dark); }
        .docs-back-home svg { flex-shrink: 0; }

        .docs-mobile-nav {
            position: sticky; top: var(--docs-header-h); z-index: 40;
            margin: 0 -1rem; padding: 0.65rem 0;
            background: linear-gradient(180deg, rgba(236,253,245,0.95) 0%, rgba(236,253,245,0.85) 100%);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(226,232,240,0.6);
        }
        @media (min-width: 1024px) { .docs-mobile-nav { display: none; } }
        .docs-mobile-nav-inner {
            display: flex; align-items: center; gap: 0.5rem; padding: 0 1rem;
        }
        .docs-mobile-nav-btn {
            flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
            width: 2.25rem; height: 2.25rem; border-radius: 0.65rem;
            border: 1px solid #e2e8f0; background: #fff; color: #475569;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        }
        .docs-mobile-track {
            display: flex; gap: 0.45rem; overflow-x: auto; scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch; scrollbar-width: none; padding: 0.1rem 0;
        }
        .docs-mobile-track::-webkit-scrollbar { display: none; }
        .docs-mobile-pill {
            flex: 0 0 auto; scroll-snap-align: start;
            border-radius: 9999px; border: 1px solid #e2e8f0; background: #fff;
            padding: 0.45rem 0.85rem; font-size: 0.75rem; font-weight: 600; color: #64748b;
            white-space: nowrap; transition: all 0.15s ease;
        }
        .docs-mobile-pill.is-active {
            border-color: transparent; color: #fff;
            background: var(--docs-primary);
            box-shadow: 0 2px 8px rgba(16,185,129,0.35);
        }

        .docs-sidebar {
            display: none;
        }
        @media (min-width: 1024px) {
            .docs-sidebar {
                display: block; position: sticky; top: calc(var(--docs-header-h) + 1.5rem);
                align-self: start; max-height: calc(100vh - var(--docs-header-h) - 3rem);
                overflow-y: auto; scrollbar-width: thin;
            }
        }
        .docs-sidebar-panel {
            border-radius: 1rem; border: 1px solid rgba(226,232,240,0.9);
            background: rgba(255,255,255,0.88); padding: 1rem;
            box-shadow: 0 4px 16px rgba(15,23,42,0.04);
            backdrop-filter: blur(8px);
        }
        .docs-nav-link {
            display: flex; align-items: center; gap: 0.6rem;
            border-radius: 0.65rem; padding: 0.5rem 0.65rem;
            font-size: 0.8125rem; font-weight: 500; color: #64748b;
            transition: color 0.15s ease, background 0.15s ease;
        }
        .docs-nav-link:hover { color: #0f172a; background: #f8fafc; }
        .docs-nav-link.is-active {
            color: var(--docs-primary-dark); background: var(--docs-badge-bg);
            font-weight: 600;
        }
        .docs-nav-num {
            flex-shrink: 0; width: 1.35rem; height: 1.35rem; border-radius: 0.4rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.65rem; font-weight: 700; color: #94a3b8; background: #f1f5f9;
        }
        .docs-nav-link.is-active .docs-nav-num {
            color: #fff; background: var(--docs-primary);
        }

        .docs-section {
            scroll-margin-top: calc(var(--docs-header-h) + 4.5rem);
            border-radius: 1.25rem; border: 1px solid rgba(226,232,240,0.85);
            background: rgba(255,255,255,0.92);
            box-shadow: 0 2px 12px rgba(15,23,42,0.04);
            overflow: hidden;
        }
        @media (min-width: 1024px) { .docs-section { scroll-margin-top: calc(var(--docs-header-h) + 1.5rem); } }
        .docs-section-head {
            display: flex; align-items: flex-start; gap: 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
            padding: 1.25rem 1.25rem 1rem;
        }
        @media (min-width: 640px) { .docs-section-head { padding: 1.5rem 1.75rem 1.15rem; gap: 1rem; } }
        .docs-section-icon {
            flex-shrink: 0; width: 2.5rem; height: 2.5rem; border-radius: 0.75rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; background: var(--docs-badge-bg);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.6);
        }
        @media (min-width: 640px) { .docs-section-icon { width: 2.75rem; height: 2.75rem; font-size: 1.25rem; } }
        .docs-section-title {
            font-size: 1.125rem; font-weight: 700; letter-spacing: -0.01em; color: #0f172a; line-height: 1.3;
        }
        @media (min-width: 640px) { .docs-section-title { font-size: 1.375rem; } }
        .docs-section-num { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 0.15rem; }
        .docs-section-body { padding: 1.15rem 1.25rem 1.5rem; }
        @media (min-width: 640px) { .docs-section-body { padding: 1.35rem 1.75rem 1.75rem; } }

        .docs-content h3 {
            margin-top: 1.5rem; margin-bottom: 0.45rem;
            font-size: 0.9375rem; font-weight: 600; color: #0f172a;
            padding-left: 0.65rem; border-left: 3px solid var(--docs-primary);
        }
        .docs-content h3:first-child { margin-top: 0; }
        .docs-content p { margin-top: 0.7rem; font-size: 0.9375rem; line-height: 1.7; color: #475569; }
        .docs-content ul, .docs-content ol { margin-top: 0.7rem; padding-left: 0; list-style: none; }
        .docs-content ul li, .docs-content ol.docs-list li {
            position: relative; margin-top: 0.5rem; padding-left: 1.35rem;
            font-size: 0.9375rem; line-height: 1.65; color: #475569;
        }
        .docs-content ul li::before {
            content: ''; position: absolute; left: 0; top: 0.55rem;
            width: 0.4rem; height: 0.4rem; border-radius: 9999px; background: var(--docs-primary);
        }

        .docs-callout {
            display: flex; gap: 0.75rem; margin-top: 1rem;
            border-radius: 0.85rem; border: 1px solid; padding: 0.95rem 1rem;
        }
        .docs-callout-icon { flex-shrink: 0; font-size: 1.1rem; line-height: 1.4; }
        .docs-callout-body { min-width: 0; }
        .docs-callout--info { border-color: #bae6fd; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); }
        .docs-callout--info .docs-callout-title { color: #0369a1; }
        .docs-callout--tip { border-color: #bbf7d0; background: linear-gradient(135deg, #f0fdf4, #dcfce7); }
        .docs-callout--tip .docs-callout-title { color: var(--docs-primary-dark); }
        .docs-callout--warning { border-color: #fde68a; background: linear-gradient(135deg, #fffbeb, #fef3c7); }
        .docs-callout--warning .docs-callout-title { color: #b45309; }
        .docs-callout-title { font-size: 0.8125rem; font-weight: 600; }
        .docs-callout-text { margin-top: 0.3rem; font-size: 0.875rem; line-height: 1.6; color: #475569; }

        .docs-table-wrap {
            margin-top: 1rem; overflow-x: auto; -webkit-overflow-scrolling: touch;
            border-radius: 0.85rem; border: 1px solid #e2e8f0;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.5);
        }
        .docs-table-scroll-hint {
            display: none; padding: 0.45rem 0.75rem; font-size: 0.6875rem; font-weight: 500;
            color: #94a3b8; background: #f8fafc; border-bottom: 1px solid #f1f5f9;
        }
        @media (max-width: 639px) {
            .docs-table-scroll-hint { display: block; }
            .docs-table { min-width: 28rem; }
        }
        .docs-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .docs-table th {
            background: #f8fafc; padding: 0.7rem 0.9rem; text-align: left;
            font-weight: 600; color: #334155; border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .docs-table td { padding: 0.65rem 0.9rem; border-bottom: 1px solid #f1f5f9; color: #475569; vertical-align: top; }
        .docs-table tr:last-child td { border-bottom: none; }
        .docs-table tbody tr:hover { background: #fafafa; }
        .docs-table code {
            display: inline-block; font-size: 0.7rem; background: #f1f5f9;
            padding: 0.15rem 0.4rem; border-radius: 0.35rem; color: #334155;
            border: 1px solid #e2e8f0; word-break: break-all;
        }

        .docs-step {
            display: flex; gap: 0.85rem; margin-top: 1rem;
            padding: 1rem; border-radius: 0.85rem; border: 1px solid #f1f5f9; background: #fafafa;
        }
        .docs-step:first-child { margin-top: 0.75rem; }
        .docs-step-num {
            flex-shrink: 0; width: 1.75rem; height: 1.75rem; border-radius: 9999px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, var(--docs-primary), var(--docs-primary-dark));
            box-shadow: 0 2px 6px rgba(16,185,129,0.3);
        }
        .docs-step-body { min-width: 0; }
        .docs-step-title { font-size: 0.9375rem; font-weight: 600; color: #0f172a; }
        .docs-step-text { margin-top: 0.3rem; font-size: 0.875rem; line-height: 1.65; color: #64748b; }

        .docs-faq-item {
            margin-top: 0.65rem; border-radius: 0.85rem; border: 1px solid #e2e8f0;
            background: #fff; overflow: hidden;
        }
        .docs-faq-item:first-child { margin-top: 0.75rem; }
        .docs-faq-q {
            display: flex; align-items: flex-start; gap: 0.5rem;
            padding: 0.9rem 1rem; font-size: 0.875rem; font-weight: 600; color: #0f172a; cursor: pointer;
            user-select: none;
        }
        .docs-faq-q::before { content: 'Q'; flex-shrink: 0; font-size: 0.65rem; font-weight: 700; color: #fff; background: var(--docs-primary); width: 1.25rem; height: 1.25rem; border-radius: 0.35rem; display: flex; align-items: center; justify-content: center; margin-top: 0.1rem; }
        .docs-faq-chevron { margin-left: auto; flex-shrink: 0; width: 1rem; height: 1rem; color: #94a3b8; transition: transform 0.2s ease; }
        .docs-faq-item.is-open .docs-faq-chevron { transform: rotate(180deg); }
        .docs-faq-a {
            max-height: 0; overflow: hidden; transition: max-height 0.25s ease;
            padding: 0 1rem; font-size: 0.875rem; line-height: 1.65; color: #64748b;
        }
        .docs-faq-item.is-open .docs-faq-a { max-height: 20rem; padding: 0 1rem 1rem 1rem; }

        .docs-subsection-card {
            margin-top: 1rem; padding: 1rem 1.1rem; border-radius: 0.85rem;
            border: 1px solid #f1f5f9; background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
        }
        .docs-subsection-card h3 { margin-top: 0; border-left: none; padding-left: 0; }

        .docs-back-top {
            position: fixed; right: 1rem; bottom: 1.25rem; z-index: 45;
            width: 2.75rem; height: 2.75rem; border-radius: 9999px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid #e2e8f0; background: #fff; color: #475569;
            box-shadow: 0 4px 16px rgba(15,23,42,0.12);
            opacity: 0; pointer-events: none; transform: translateY(0.5rem);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .docs-back-top.is-visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
        .docs-back-top:hover { color: var(--docs-primary-dark); border-color: var(--docs-primary); }

        .docs-nav-drawer {
            position: fixed; inset: 0; z-index: 55; pointer-events: none; opacity: 0;
            transition: opacity 0.2s ease;
        }
        .docs-nav-drawer.is-open { pointer-events: auto; opacity: 1; }
        .docs-nav-drawer-backdrop {
            position: absolute; inset: 0; background: rgba(15,23,42,0.4);
            backdrop-filter: blur(2px);
        }
        .docs-nav-drawer-panel {
            position: absolute; left: 0; right: 0; bottom: 0; max-height: 70vh;
            border-radius: 1.25rem 1.25rem 0 0; background: #fff;
            box-shadow: 0 -8px 32px rgba(15,23,42,0.15);
            transform: translateY(100%); transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
            display: flex; flex-direction: column;
        }
        .docs-nav-drawer.is-open .docs-nav-drawer-panel { transform: translateY(0); }
        .docs-nav-drawer-handle {
            flex-shrink: 0; padding: 0.75rem 1rem 0.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .docs-nav-drawer-bar { width: 2.5rem; height: 0.25rem; border-radius: 9999px; background: #e2e8f0; margin: 0 auto 0.75rem; }
        .docs-nav-drawer-title { font-size: 0.9375rem; font-weight: 600; color: #0f172a; text-align: center; }
        .docs-nav-drawer-list {
            overflow-y: auto; padding: 0.75rem 1rem 1.25rem;
            -webkit-overflow-scrolling: touch;
        }
        .docs-nav-drawer-link {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.75rem 0.85rem; border-radius: 0.75rem;
            font-size: 0.9375rem; font-weight: 500; color: #334155;
            border: 1px solid transparent;
        }
        .docs-nav-drawer-link:hover, .docs-nav-drawer-link.is-active {
            background: var(--docs-badge-bg); color: var(--docs-primary-dark); border-color: rgba(16,185,129,0.15);
        }

        @media (prefers-reduced-motion: reduce) {
            .docs-progress-bar, .docs-back-top, .docs-nav-drawer, .docs-nav-drawer-panel, .docs-faq-a, .docs-faq-chevron { transition: none; }
        }
    </style>
</head>
<body class="min-h-screen text-slate-900 antialiased">
    <div class="docs-progress" aria-hidden="true"><div class="docs-progress-bar" data-docs-progress></div></div>

    <div class="docs-gradient flex min-h-screen flex-col">
        @include('partials.public-site-header', ['activePage' => 'docs', 'welcome' => $welcome])

        <div class="docs-mobile-nav" aria-label="{{ $docUi['section_navigation'] ?? 'Section navigation' }}">
            <div class="docs-mobile-nav-inner">
                <button type="button" class="docs-mobile-nav-btn" data-docs-menu-open aria-label="{{ $docUi['open_section_menu'] ?? 'Open section menu' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="docs-mobile-track min-w-0 flex-1" data-docs-mobile-track>
                    @foreach ($sections as $index => $section)
                        <a href="#{{ $section['id'] }}" class="docs-mobile-pill{{ $index === 0 ? ' is-active' : '' }}" data-docs-nav="{{ $section['id'] }}">{{ $section['title'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mx-auto w-full max-w-6xl flex-1 px-4 pb-10 pt-6 sm:px-6 sm:pb-14 sm:pt-8 lg:px-8 lg:pb-16">
            <div class="docs-hero mb-8 lg:mb-10">
                <div class="docs-hero-glow" aria-hidden="true"></div>
                <div class="relative">
                    <a href="{{ route('welcome') }}" class="docs-back-home">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                        {{ $welcome['home_label'] ?? 'Home' }}
                    </a>
                    <span class="docs-badge inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide">{{ $docUi['user_guide'] ?? 'User guide' }}</span>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:mt-4 sm:text-3xl lg:text-4xl">{{ $docs['title'] ?? 'Documentation' }}</h1>
                    @if (! empty($docs['subtitle']))
                        <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 sm:text-base">{{ $docs['subtitle'] }}</p>
                    @endif
                    <div class="docs-hero-meta">
                        <span class="docs-hero-chip">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            {{ str_replace(':count', (string) count($sections), $docUi['sections_count'] ?? ':count sections') }}
                        </span>
                        @if (! empty($docs['last_updated']))
                            <span class="docs-hero-chip">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                {{ str_replace(':date', $docs['last_updated'], $docUi['updated'] ?? 'Updated :date') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid gap-8 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-10 xl:grid-cols-[260px_minmax(0,1fr)] xl:gap-12">
                <aside class="docs-sidebar" aria-label="Documentation sections">
                    <nav class="docs-sidebar-panel">
                        <p class="mb-3 px-1 text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-400">{{ $docUi['contents'] ?? 'Contents' }}</p>
                        <ul class="space-y-0.5">
                            @foreach ($sections as $index => $section)
                                <li>
                                    <a href="#{{ $section['id'] }}" class="docs-nav-link{{ $index === 0 ? ' is-active' : '' }}" data-docs-nav="{{ $section['id'] }}">
                                        <span class="docs-nav-num">{{ $index + 1 }}</span>
                                        <span class="min-w-0 truncate">{{ $section['title'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </aside>

                <main class="min-w-0 space-y-5 sm:space-y-6">
                    @foreach ($sections as $index => $section)
                        <section id="{{ $section['id'] }}" class="docs-section">
                            <div class="docs-section-head">
                                <div class="docs-section-icon" aria-hidden="true">{{ $sectionIcons[$section['id']] ?? '📄' }}</div>
                                <div class="min-w-0">
                                    <p class="docs-section-num">{{ str_replace(':num', (string) ($index + 1), $docUi['section'] ?? 'Section :num') }}</p>
                                    <h2 class="docs-section-title">{{ $section['title'] }}</h2>
                                </div>
                            </div>
                            <div class="docs-section-body docs-content">
                                @include('documentation.partials.blocks', ['blocks' => $section['blocks'] ?? [], 'docUi' => $docUi])
                            </div>
                        </section>
                    @endforeach
                </main>
            </div>
        </div>

        <footer class="border-t border-slate-200/70 bg-white/75 backdrop-blur-sm">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                <span>{{ config('app.name') }} · {{ $docUi['documentation_footer'] ?? 'Documentation' }}</span>
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ route('welcome') }}" class="font-medium transition hover:text-slate-700">{{ $welcome['home_label'] ?? 'Home' }}</a>
                    <a href="{{ route('login') }}" class="font-medium transition hover:text-slate-700">{{ $welcome['sign_in'] ?? 'Sign in' }}</a>
                </div>
            </div>
        </footer>
    </div>

    <button type="button" class="docs-back-top" data-docs-back-top aria-label="{{ $docUi['back_to_top'] ?? 'Back to top' }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
    </button>

    <div class="docs-nav-drawer" data-docs-drawer aria-hidden="true">
        <div class="docs-nav-drawer-backdrop" data-docs-drawer-close></div>
        <div class="docs-nav-drawer-panel" role="dialog" aria-label="{{ $docUi['jump_to_section'] ?? 'Jump to section' }}">
            <div class="docs-nav-drawer-handle">
                <div class="docs-nav-drawer-bar"></div>
                <p class="docs-nav-drawer-title">{{ $docUi['jump_to_section'] ?? 'Jump to section' }}</p>
            </div>
            <div class="docs-nav-drawer-list">
                @foreach ($sections as $index => $section)
                    <a href="#{{ $section['id'] }}" class="docs-nav-drawer-link{{ $index === 0 ? ' is-active' : '' }}" data-docs-nav="{{ $section['id'] }}" data-docs-drawer-close>
                        <span class="docs-nav-num">{{ $index + 1 }}</span>
                        <span>{{ $sectionIcons[$section['id']] ?? '📄' }} {{ $section['title'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        (function () {
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const navLinks = document.querySelectorAll('[data-docs-nav]');
            const sections = Array.from(navLinks)
                .map((link) => document.getElementById(link.dataset.docsNav))
                .filter(Boolean);
            const uniqueSections = [...new Set(sections)];

            function setActive(id) {
                navLinks.forEach((link) => {
                    const active = link.dataset.docsNav === id;
                    link.classList.toggle('is-active', active);
                    if (active && link.classList.contains('docs-mobile-pill')) {
                        link.scrollIntoView({ inline: 'center', block: 'nearest', behavior: reducedMotion ? 'auto' : 'smooth' });
                    }
                });
            }

            if (uniqueSections.length) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) setActive(entry.target.id);
                    });
                }, { rootMargin: '-25% 0px -55% 0px', threshold: 0 });
                uniqueSections.forEach((section) => observer.observe(section));
                setActive(uniqueSections[0].id);
            }

            const progressBar = document.querySelector('[data-docs-progress]');
            const backTop = document.querySelector('[data-docs-back-top]');
            function onScroll() {
                const scrollTop = window.scrollY;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                if (progressBar && docHeight > 0) {
                    progressBar.style.width = Math.min(100, (scrollTop / docHeight) * 100) + '%';
                }
                if (backTop) {
                    backTop.classList.toggle('is-visible', scrollTop > 400);
                }
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            if (backTop) {
                backTop.addEventListener('click', () => {
                    window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
                });
            }

            navLinks.forEach((link) => {
                link.addEventListener('click', () => closeDrawer());
            });

            const drawer = document.querySelector('[data-docs-drawer]');
            const openBtn = document.querySelector('[data-docs-menu-open]');
            function openDrawer() {
                if (!drawer) return;
                drawer.classList.add('is-open');
                drawer.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
            function closeDrawer() {
                if (!drawer) return;
                drawer.classList.remove('is-open');
                drawer.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
            openBtn?.addEventListener('click', openDrawer);
            drawer?.querySelectorAll('[data-docs-drawer-close]').forEach((el) => {
                el.addEventListener('click', closeDrawer);
            });

            document.querySelectorAll('.docs-faq-q').forEach((question) => {
                question.addEventListener('click', () => {
                    const item = question.closest('.docs-faq-item');
                    const wasOpen = item.classList.contains('is-open');
                    item.parentElement.querySelectorAll('.docs-faq-item').forEach((i) => i.classList.remove('is-open'));
                    if (!wasOpen) item.classList.add('is-open');
                });
            });

            if (window.matchMedia('(max-width: 1023px)').matches) {
                document.querySelectorAll('.docs-faq-item').forEach((item, i) => {
                    if (i === 0) item.classList.add('is-open');
                });
            } else {
                document.querySelectorAll('.docs-faq-item').forEach((item) => item.classList.add('is-open'));
            }
        })();
    </script>
</body>
</html>

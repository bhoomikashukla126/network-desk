@php
    $welcome = $welcome ?? config('welcome');
    $ui = $welcome['ui'] ?? [];
    $accent = $welcome['accent'] ?? [];
    $hero = $welcome['hero'] ?? [];
    $features = $welcome['features'] ?? [];
    $highlights = $welcome['highlights'] ?? [];
    $preview = $welcome['preview'] ?? null;
    $gallery = $welcome['gallery'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $hero['subtitle'] ?? config('app.name') }}">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        .welcome-gradient {
            background:
                radial-gradient(ellipse 80% 60% at 10% 0%, {{ $accent['glow'] ?? 'rgba(245,158,11,0.18)' }}, transparent 55%),
                radial-gradient(ellipse 70% 50% at 90% 10%, {{ $accent['glow_secondary'] ?? 'rgba(217,119,6,0.12)' }}, transparent 50%),
                linear-gradient(180deg, {{ $accent['gradient_from'] ?? '#fffbeb' }} 0%, {{ $accent['gradient_to'] ?? '#ffffff' }} 100%);
        }
        .welcome-primary { background-color: {{ $accent['primary'] ?? '#f59e0b' }}; }
        .welcome-primary:hover { background-color: {{ $accent['primary_dark'] ?? '#d97706' }}; }
        .welcome-badge {
            background-color: {{ $accent['badge_bg'] ?? 'rgba(245,158,11,0.14)' }};
            color: {{ $accent['primary_dark'] ?? '#d97706' }};
        }
        .welcome-feature-icon {
            background-color: {{ $accent['badge_bg'] ?? 'rgba(245,158,11,0.14)' }};
            color: {{ $accent['primary'] ?? '#f59e0b' }};
        }
        .welcome-tag {
            background-color: {{ $accent['badge_bg'] ?? 'rgba(245,158,11,0.14)' }};
            color: {{ $accent['primary_dark'] ?? '#d97706' }};
        }

        /* ── Depth-stack hero (mobile-first, 3D on desktop) ── */
        .depth-hero {
            position: relative;
            padding: 1.5rem 0 2.5rem;
        }

        .depth-glow {
            position: absolute;
            inset: -15% -10%;
            border-radius: 50%;
            background: radial-gradient(circle, {{ $accent['glow'] ?? 'rgba(245,158,11,0.2)' }}, transparent 68%);
            pointer-events: none;
            z-index: 0;
        }

        .depth-stack {
            position: relative;
            max-width: 540px;
            margin: 0 auto;
            z-index: 1;
        }

        .depth-layer {
            position: absolute;
            left: 0;
            right: 0;
            border-radius: 1.15rem;
            border: 1px solid rgba(148,163,184,0.25);
            background: rgba(255,255,255,0.55);
            backdrop-filter: blur(6px);
            pointer-events: none;
        }

        .depth-layer--1 {
            top: 14px;
            bottom: -10px;
            transform: scale(0.94) translateY(10px);
            opacity: 0.35;
            z-index: 0;
        }

        .depth-layer--2 {
            top: 7px;
            bottom: -5px;
            transform: scale(0.97) translateY(5px);
            opacity: 0.55;
            z-index: 1;
            border-color: rgba(148,163,184,0.35);
        }

        .depth-device {
            position: relative;
            z-index: 2;
            border-radius: 1.2rem;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.85);
            background: #fff;
            box-shadow:
                0 4px 6px rgba(15,23,42,0.04),
                0 16px 40px rgba(15,23,42,0.1),
                0 0 0 1px rgba(148,163,184,0.08);
            transition: box-shadow 0.4s ease, transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .depth-chrome {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.65rem 1rem;
            background: linear-gradient(180deg, #f8fafc, #eef2f7);
            border-bottom: 1px solid rgba(148,163,184,0.25);
        }

        .depth-dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 9999px;
            background: #cbd5e1;
        }

        .depth-dot--accent {
            background: {{ $accent['primary'] ?? '#f59e0b' }};
        }

        .depth-title {
            margin-left: 0.35rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .depth-screen img {
            display: block;
            width: 100%;
            height: auto;
        }

        .depth-shadow {
            position: absolute;
            left: 12%;
            right: 12%;
            bottom: -14px;
            height: 20px;
            border-radius: 50%;
            background: radial-gradient(ellipse, rgba(15,23,42,0.18), transparent 72%);
            filter: blur(5px);
            z-index: 0;
        }

        @media (min-width: 1024px) {
            .depth-hero {
                perspective: 1300px;
                perspective-origin: 50% 42%;
            }
            .depth-stack {
                transform-style: preserve-3d;
                transform: rotateX(4deg) rotateY(-6deg);
            }
            .depth-layer--1 { transform: scale(0.94) translateY(10px) translateZ(-50px); }
            .depth-layer--2 { transform: scale(0.97) translateY(5px) translateZ(-24px); }
            .depth-device { transform: translateZ(32px); transform-style: preserve-3d; }
            .depth-stack.is-tilting .depth-device {
                box-shadow:
                    0 8px 16px rgba(15,23,42,0.06),
                    0 28px 56px rgba(15,23,42,0.14),
                    0 0 40px {{ $accent['glow'] ?? 'rgba(245,158,11,0.15)' }};
            }
        }

        /* ── Snap-scroll gallery ── */
        .snap-gallery { margin-top: 2rem; }
        .snap-shell { display: flex; align-items: center; gap: 0.65rem; }
        .snap-nav {
            display: none; flex-shrink: 0; align-items: center; justify-content: center;
            width: 2.5rem; height: 2.5rem; border-radius: 9999px;
            border: 1px solid rgba(148,163,184,0.45); background: #fff; color: #475569;
            cursor: pointer; box-shadow: 0 2px 8px rgba(15,23,42,0.06);
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, opacity 0.2s ease;
        }
        .snap-nav:hover:not(:disabled) { background: #f8fafc; border-color: rgba(148,163,184,0.7); color: #1e293b; }
        .snap-nav:disabled { opacity: 0.35; cursor: not-allowed; }
        @media (min-width: 768px) { .snap-nav { display: flex; } }
        .snap-shell .snap-track-wrap { flex: 1; min-width: 0; }
        .snap-footer { margin-top: 0.25rem; }
        .snap-counter {
            display: none; text-align: center; font-size: 0.75rem; font-weight: 500; color: #94a3b8;
        }
        @media (min-width: 768px) { .snap-counter { display: block; } }


        .snap-hint {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-bottom: 0.75rem;
            font-size: 0.7rem;
            font-weight: 500;
            color: #94a3b8;
            letter-spacing: 0.02em;
        }

        @media (min-width: 768px) {
            .snap-hint { display: none; }
        }

        .snap-track-wrap {
            position: relative;
            margin: 0 -1rem;
        }

        @media (min-width: 640px) {
            .snap-track-wrap { margin: 0; }
        }

        .snap-track {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-padding-inline: 1rem;
            -webkit-overflow-scrolling: touch;
            padding: 0.75rem 1rem 1.75rem;
            scrollbar-width: none;
        }

        .snap-track::-webkit-scrollbar { display: none; }

        .snap-card {
            flex: 0 0 min(88vw, 340px);
            scroll-snap-align: center;
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.35s ease;
            transform: scale(0.93);
            opacity: 0.7;
        }

        @media (min-width: 640px) {
            .snap-card { flex: 0 0 min(70vw, 400px); }
        }

        @media (min-width: 1024px) {
            .snap-card { flex: 0 0 calc(50% - 0.5rem); }
        }

        .snap-card.is-active {
            transform: scale(1);
            opacity: 1;
        }

        .snap-card-inner {
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid rgba(148,163,184,0.3);
            background: #fff;
            box-shadow: 0 6px 20px rgba(15,23,42,0.07);
            transition: box-shadow 0.35s ease, transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .snap-card.is-active .snap-card-inner {
            border-color: rgba(148,163,184,0.45);
            box-shadow:
                0 16px 40px rgba(15,23,42,0.12),
                0 0 0 1px {{ $accent['badge_bg'] ?? 'rgba(245,158,11,0.12)' }};
        }

        .snap-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            background: linear-gradient(180deg, #f8fafc, #fff);
        }

        .snap-card-head figcaption {
            font-size: 0.75rem;
            font-weight: 600;
            color: #334155;
            line-height: 1.35;
        }

        .snap-card-body {
            padding: 0.5rem;
            background: linear-gradient(145deg, #f8fafc, #f1f5f9);
        }

        .snap-card-body img {
            display: block;
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
        }

        .snap-dots {
            display: flex;
            justify-content: center;
            gap: 0.4rem;
            margin-top: 0.25rem;
        }

        .snap-dot {
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 9999px;
            background: #cbd5e1;
            border: none;
            padding: 0;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .snap-dot.is-active {
            background: {{ $accent['primary'] ?? '#f59e0b' }};
            transform: scale(1.25);
        }

        @media (min-width: 768px) {
            .snap-dots { display: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .depth-stack,
            .depth-device,
            .snap-card,
            .snap-card-inner {
                transform: none !important;
                transition: none !important;
            }
            .snap-card { opacity: 1; }
        }
    </style>
</head>
<body class="min-h-screen text-slate-900 antialiased">
    <div class="welcome-gradient flex min-h-screen flex-col">
        @include('partials.public-site-header', ['activePage' => 'welcome', 'welcome' => $welcome])

        <main class="flex-1">
            <section class="mx-auto grid max-w-6xl gap-10 px-4 pb-12 pt-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8 lg:pb-16 lg:pt-8">
                <div>
                    @if (! empty($hero['badge']))
                        <span class="welcome-badge inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide">{{ $hero['badge'] }}</span>
                    @endif
                    <h1 class="mt-5 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-[3.25rem] lg:leading-[1.1]">{{ $hero['title'] ?? config('app.name') }}</h1>
                    <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">{{ $hero['subtitle'] ?? '' }}</p>

                    @if (count($highlights) > 0)
                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            @foreach ($highlights as $highlight)
                                <div class="rounded-xl border border-slate-200/80 bg-white/80 p-3 shadow-sm backdrop-blur-sm">
                                    <div class="text-lg">{{ $highlight['icon'] ?? '•' }}</div>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $highlight['title'] }}</p>
                                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ $highlight['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <a href="{{ route('login') }}" class="welcome-primary inline-flex items-center justify-center rounded-xl px-6 py-3.5 text-sm font-semibold text-white shadow-md transition">{{ $welcome['cta'] ?? 'Sign in to your workspace' }}</a>
                        @if (! empty($welcome['central_url']))
                            <a href="{{ $welcome['central_url'] }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white/80 px-6 py-3.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white">{{ $welcome['central_label'] ?? 'Platform home' }}</a>
                        @endif
                    </div>
                    <p class="mt-4 max-w-lg text-sm text-slate-500">{{ $welcome['auth_note'] ?? '' }}</p>
                </div>

                <div class="depth-hero relative mx-auto w-full max-w-xl lg:max-w-none">
                    <div class="depth-glow"></div>
                    <div class="depth-stack" data-depth-tilt>
                        <div class="depth-layer depth-layer--1"></div>
                        <div class="depth-layer depth-layer--2"></div>
                        <div class="depth-device">
                            <div class="depth-chrome">
                                <span class="depth-dot"></span>
                                <span class="depth-dot"></span>
                                <span class="depth-dot depth-dot--accent"></span>
                                <span class="depth-title">{{ config('app.name') }}</span>
                            </div>
                            <div class="depth-screen">
                                @if (! empty($preview['image']))
                                    <img src="{{ asset($preview['image']) }}" alt="{{ $preview['alt'] ?? 'App preview' }}">
                                @else
                                    <img src="{{ asset($welcome['hero_image'] ?? 'images/welcome/hero.svg') }}" alt="{{ $hero['image_alt'] ?? config('app.name') }}">
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="depth-shadow"></div>
                </div>
            </section>

            @if (count($gallery) > 0)
                <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $preview['heading'] ?? 'Inside the app' }}</h2>
                        @if (! empty($preview['subheading']))
                            <p class="mt-3 text-sm leading-relaxed text-slate-600 sm:text-base">{{ $preview['subheading'] }}</p>
                        @endif
                    </div>

                    <div class="snap-gallery" data-snap-gallery>
                        <p class="snap-hint">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M5 12l4-4M5 12l4 4"/></svg>
                            {{ $ui['swipe_hint'] ?? 'Swipe to explore' }}
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M19 12l-4-4M19 12l-4 4"/></svg>
                        </p>
                        <div class="snap-shell">
                            <button type="button" class="snap-nav" data-snap-prev aria-label="Previous screenshot" disabled>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                            </button>
                            <div class="snap-track-wrap">
                            <div class="snap-track" data-snap-track>
                                @foreach ($gallery as $index => $shot)
                                    <figure class="snap-card{{ $index === 0 ? ' is-active' : '' }}" data-snap-card>
                                        <div class="snap-card-inner">
                                            <div class="snap-card-head">
                                                <figcaption>{{ $shot['caption'] ?? '' }}</figcaption>
                                                @if (! empty($shot['tag']))
                                                    <span class="welcome-tag shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">{{ $shot['tag'] }}</span>
                                                @endif
                                            </div>
                                            <div class="snap-card-body">
                                                <img src="{{ asset($shot['image']) }}" alt="{{ $shot['caption'] ?? ($ui['app_screenshot'] ?? 'App screenshot') }}" loading="lazy">
                                            </div>
                                        </div>
                                    </figure>
                                @endforeach
                            </div>
                            </div>
                            <button type="button" class="snap-nav" data-snap-next aria-label="Next screenshot">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                            </button>
                        </div>
                        <div class="snap-footer">
                            <div class="snap-dots" data-snap-dots>
                            @foreach ($gallery as $index => $shot)
                                <button type="button" class="snap-dot{{ $index === 0 ? ' is-active' : '' }}" aria-label="{{ str_replace(':num', (string) ($index + 1), $ui['slide_label'] ?? 'Go to slide :num') }}" data-snap-dot="{{ $index }}"></button>
                            @endforeach
                            </div>
                            <p class="snap-counter" data-snap-counter aria-live="polite"></p>
                        </div>
                    </div>
                </section>
            @elseif ($preview)
                <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6 lg:px-8">
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg shadow-slate-200/50">
                        <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $preview['label'] ?? 'Inside the app' }}</p>
                        </div>
                        <div class="relative bg-slate-100/60 p-2 sm:p-4">
                            @if (! empty($preview['image']))
                                <img src="{{ asset($preview['image']) }}" alt="{{ $preview['alt'] ?? 'App preview' }}" class="w-full rounded-xl border border-slate-200 shadow-sm">
                            @else
                                <div class="flex aspect-[16/10] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 text-center">
                                    <div class="welcome-feature-icon mb-4 flex h-14 w-14 items-center justify-center rounded-2xl text-2xl">{{ $welcome['icon'] ?? '◆' }}</div>
                                    <p class="text-sm font-medium text-slate-700">{{ $preview['placeholder_title'] ?? 'App preview coming soon' }}</p>
                                    <p class="mt-1 max-w-md text-xs text-slate-500">{{ $preview['placeholder_text'] ?? '' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            @if (count($features) > 0)
                <section class="border-t border-slate-200/70 bg-white/60 backdrop-blur-sm">
                    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
                        <div class="mx-auto max-w-2xl text-center">
                            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $welcome['features_heading'] ?? 'Everything you need' }}</h2>
                            @if (! empty($welcome['features_subheading']))
                                <p class="mt-3 text-sm leading-relaxed text-slate-600 sm:text-base">{{ $welcome['features_subheading'] }}</p>
                            @endif
                        </div>
                        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($features as $feature)
                                <article class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:border-slate-300 hover:shadow-md">
                                    <div class="welcome-feature-icon flex h-11 w-11 items-center justify-center rounded-xl text-xl">{{ $feature['icon'] ?? '•' }}</div>
                                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $feature['description'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </main>

        <footer class="border-t border-slate-200/70 bg-white/70 backdrop-blur-sm">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                <span>{{ config('app.name') }}</span>
                <div class="flex items-center gap-4">
                    <a href="{{ route('docs') }}" class="transition hover:text-slate-700">{{ $welcome['docs_label'] ?? 'Documentation' }}</a>
                    <span>{{ $welcome['footer'] ?? 'Secure workspace extension' }}</span>
                </div>
            </div>
        </footer>
    </div>
    <script>
        (function () {
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const finePointer = window.matchMedia('(pointer: fine)').matches;
            const desktop = window.matchMedia('(min-width: 1024px)').matches;

            /* Hero tilt — desktop + fine pointer only */
            if (!reducedMotion && finePointer && desktop) {
                document.querySelectorAll('[data-depth-tilt]').forEach((stack) => {
                    stack.addEventListener('pointermove', (e) => {
                        const rect = stack.getBoundingClientRect();
                        const x = (e.clientX - rect.left) / rect.width - 0.5;
                        const y = (e.clientY - rect.top) / rect.height - 0.5;
                        stack.classList.add('is-tilting');
                        stack.style.transform = `rotateX(${4 - y * 8}deg) rotateY(${-6 + x * 12}deg)`;
                    });
                    stack.addEventListener('pointerleave', () => {
                        stack.classList.remove('is-tilting');
                        stack.style.transform = '';
                    });
                });
            }

            /* Snap gallery */
            document.querySelectorAll('[data-snap-gallery]').forEach((gallery) => {
                const track = gallery.querySelector('[data-snap-track]');
                const cards = gallery.querySelectorAll('[data-snap-card]');
                const dots = gallery.querySelectorAll('[data-snap-dot]');
                if (!track || !cards.length) return;

                const prevBtn = gallery.querySelector('[data-snap-prev]');
                const nextBtn = gallery.querySelector('[data-snap-next]');
                const counter = gallery.querySelector('[data-snap-counter]');
                let activeIndex = 0;

                function setActive(index) {
                    activeIndex = index;
                    cards.forEach((card, i) => card.classList.toggle('is-active', i === index));
                    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
                    if (prevBtn) prevBtn.disabled = index === 0;
                    if (nextBtn) nextBtn.disabled = index === cards.length - 1;
                    if (counter) counter.textContent = `${index + 1} / ${cards.length}`;
                }

                function scrollToIndex(index) {
                    const card = cards[index];
                    if (!card) return;
                    track.scrollTo({
                        left: card.offsetLeft - (track.clientWidth - card.clientWidth) / 2,
                        behavior: reducedMotion ? 'auto' : 'smooth',
                    });
                    setActive(index);
                }

                setActive(0);

                if (prevBtn) prevBtn.addEventListener('click', () => scrollToIndex(activeIndex - 1));
                if (nextBtn) nextBtn.addEventListener('click', () => scrollToIndex(activeIndex + 1));

                function updateFromScroll() {
                    const trackRect = track.getBoundingClientRect();
                    const center = trackRect.left + trackRect.width / 2;
                    let closest = 0;
                    let closestDist = Infinity;
                    cards.forEach((card, i) => {
                        const cardRect = card.getBoundingClientRect();
                        const cardCenter = cardRect.left + cardRect.width / 2;
                        const dist = Math.abs(center - cardCenter);
                        if (dist < closestDist) {
                            closestDist = dist;
                            closest = i;
                        }
                    });
                    if (closest !== activeIndex) setActive(closest);
                }

                track.addEventListener('scroll', () => requestAnimationFrame(updateFromScroll), { passive: true });

                dots.forEach((dot) => {
                    dot.addEventListener('click', () => {
                        const index = parseInt(dot.dataset.snapDot, 10);
                        const card = cards[index];
                        scrollToIndex(index);
                    });
                });

                if (!reducedMotion && finePointer) {
                    cards.forEach((card) => {
                        card.addEventListener('pointermove', (e) => {
                            if (!card.classList.contains('is-active')) return;
                            const rect = card.getBoundingClientRect();
                            const x = (e.clientX - rect.left) / rect.width - 0.5;
                            const y = (e.clientY - rect.top) / rect.height - 0.5;
                            const inner = card.querySelector('.snap-card-inner');
                            if (inner) inner.style.transform = `rotateX(${-y * 4}deg) rotateY(${x * 6}deg)`;
                        });
                        card.addEventListener('pointerleave', () => {
                            const inner = card.querySelector('.snap-card-inner');
                            if (inner) inner.style.transform = '';
                        });
                    });
                }
            });
        })();
    </script>
</body>
</html>

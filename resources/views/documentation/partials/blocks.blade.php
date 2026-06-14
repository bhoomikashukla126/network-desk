@php
    $docUi = $docUi ?? [];
@endphp
@foreach ($blocks as $block)
    @switch($block['type'] ?? '')
        @case('paragraph')
            <p>{{ $block['text'] ?? '' }}</p>
            @break

        @case('list')
            @if (! empty($block['title']))
                <h3>{{ $block['title'] }}</h3>
            @endif
            <ul class="docs-list">
                @foreach ($block['items'] ?? [] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            @break

        @case('steps')
            <ol class="list-none pl-0">
                @foreach ($block['items'] ?? [] as $index => $step)
                    <li class="docs-step">
                        <span class="docs-step-num" aria-hidden="true">{{ $index + 1 }}</span>
                        <div class="docs-step-body">
                            <p class="docs-step-title">{{ $step['title'] ?? '' }}</p>
                            <p class="docs-step-text">{{ $step['text'] ?? '' }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            @break

        @case('callout')
            @php
                $variant = $block['variant'] ?? 'info';
                $icons = ['info' => 'ℹ️', 'tip' => '✅', 'warning' => '⚠️'];
            @endphp
            <div class="docs-callout docs-callout--{{ $variant }}">
                <span class="docs-callout-icon" aria-hidden="true">{{ $icons[$variant] ?? 'ℹ️' }}</span>
                <div class="docs-callout-body">
                    @if (! empty($block['title']))
                        <p class="docs-callout-title">{{ $block['title'] }}</p>
                    @endif
                    <p class="docs-callout-text">{{ $block['text'] ?? '' }}</p>
                </div>
            </div>
            @break

        @case('subsection')
            <div class="docs-subsection-card">
                <h3>{{ $block['title'] ?? '' }}</h3>
                @include('documentation.partials.blocks', ['blocks' => $block['blocks'] ?? [], 'docUi' => $docUi])
            </div>
            @break

        @case('table')
            @if (! empty($block['title']))
                <h3>{{ $block['title'] }}</h3>
            @endif
            <div class="docs-table-wrap">
                <p class="docs-table-scroll-hint">{{ $docUi['scroll_table'] ?? 'Scroll horizontally to see all columns' }} →</p>
                <table class="docs-table">
                    <thead>
                        <tr>
                            @foreach ($block['headers'] ?? [] as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($block['rows'] ?? [] as $row)
                            <tr>
                                @foreach ($row as $cellIndex => $cell)
                                    <td data-label="{{ ($block['headers'] ?? [])[$cellIndex] ?? '' }}">
                                        @if (str_contains($cell, '.') && ! str_contains($cell, ' '))
                                            <code>{{ $cell }}</code>
                                        @else
                                            {{ $cell }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @break

        @case('faq')
            <div class="docs-faq">
                @foreach ($block['items'] ?? [] as $item)
                    <div class="docs-faq-item">
                        <p class="docs-faq-q" role="button" tabindex="0" aria-expanded="false">
                            <span>{{ $item['question'] ?? '' }}</span>
                            <svg class="docs-faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                        </p>
                        <p class="docs-faq-a">{{ $item['answer'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
            @break
    @endswitch
@endforeach

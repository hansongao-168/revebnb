@props([
    'active' => null,
    'filters' => [],
])

<div class="border-b border-ink-100 bg-cream-50/95 backdrop-blur sticky top-20 z-30">
    <div class="site-shell">
        <div class="scroll-strip flex items-end gap-7 overflow-x-auto py-3">
            @foreach ($siteNav['category_strip'] ?? [] as $item)
                @php
                    $categoryKey = $item->routeParams['category'] ?? null;
                    $isActive = ($active ?? null) === $categoryKey;
                    $params = array_filter([
                        'destination' => $filters['destination'] ?? null,
                        'check_in' => $filters['check_in'] ?? null,
                        'check_out' => $filters['check_out'] ?? null,
                        'guests' => $filters['guests'] ?? null,
                        'category' => $categoryKey,
                    ], fn ($v) => $v !== null && $v !== '');
                @endphp
                <a href="{{ $item->href($params) }}"
                   class="category-chip text-[12px] font-medium"
                   data-active="{{ $isActive ? 'true' : 'false' }}">
                    @if ($item->icon)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                             stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="{{ $item->icon }}"/>
                        </svg>
                    @endif
                    <span>{{ $item->title }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

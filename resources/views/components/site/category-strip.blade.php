@props([
    'active' => null,
    'filters' => [],
])

@php
    $items = [
        ['key' => null,           'label' => '全部',       'icon' => 'M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-8.5Z'],
        ['key' => 'editor-pick',  'label' => '编辑精选',   'icon' => 'M12 2 14.6 8.8 22 9.6l-5.6 4.9L18.1 22 12 18.3 5.9 22l1.7-7.5L2 9.6l7.4-.8L12 2Z'],
        ['key' => 'design',       'label' => '设计住宅',   'icon' => 'M4 21V7l8-4 8 4v14M9 21v-6h6v6'],
        ['key' => 'beachfront',   'label' => '海景房',     'icon' => 'M2 16c2 2 4 0 6 0s4 2 6 0 4 0 6 0M2 20c2 2 4 0 6 0s4 2 6 0 4 0 6 0M14 6a3 3 0 0 0-3-3 5 5 0 0 0-5 5'],
        ['key' => 'mountain',     'label' => '山景小屋',   'icon' => 'M3 20 9 9l4 6 3-4 5 9H3Z'],
        ['key' => 'city',         'label' => '都市公寓',   'icon' => 'M3 21V7l5-3 5 3v14M13 21V11l4-2 4 2v10M7 11v0M7 14v0M7 17v0'],
        ['key' => 'cabin',        'label' => '林间木屋',   'icon' => 'M3 20 12 5l9 15H3Zm5 0v-6h8v6'],
        ['key' => 'long-stay',    'label' => '长期旅居',   'icon' => 'M5 4h14v6H5zM5 14h14v6H5zM9 7h.01M9 17h.01'],
        ['key' => 'luxe',         'label' => 'Luxe 精品', 'icon' => 'M4 8h16l-2 12H6L4 8Zm4 0a4 4 0 0 1 8 0'],
        ['key' => 'wechat',       'label' => '微信好评',   'icon' => 'M9.5 4a7 7 0 0 0-6 11l-.7 3 3.2-1.2A7 7 0 1 0 9.5 4Z'],
        ['key' => 'unique',       'label' => '独特住宿',   'icon' => 'M6 19V10l6-6 6 6v9M9 19v-5h6v5'],
    ];
@endphp

<div class="border-b border-ink-100 bg-cream-50/95 backdrop-blur sticky top-20 z-30">
    <div class="site-shell">
        <div class="scroll-strip flex items-end gap-7 overflow-x-auto py-3">
            @foreach ($items as $item)
                @php
                    $isActive = ($active ?? null) === $item['key'];
                    $params = array_filter([
                        'destination' => $filters['destination'] ?? null,
                        'check_in' => $filters['check_in'] ?? null,
                        'check_out' => $filters['check_out'] ?? null,
                        'guests' => $filters['guests'] ?? null,
                        'category' => $item['key'],
                    ], fn ($v) => $v !== null && $v !== '');
                @endphp
                <a href="{{ route('site.stays.index', $params) }}"
                   class="category-chip text-[12px] font-medium"
                   data-active="{{ $isActive ? 'true' : 'false' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                         stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <path d="{{ $item['icon'] }}"/>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

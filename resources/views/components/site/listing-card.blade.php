@props([
    'listing',
])

@php
    $images = $listing->images;
    $cover = $images->firstWhere('is_cover', true) ?? $images->first();
    $coverUrl = $cover?->path ? \App\Support\ListingImageUrl::url($cover->path) : 'https://picsum.photos/seed/'.$listing->id.'/1200/1400';
    $secondary = $images->where('id', '!=', $cover?->id)->take(3);
    $rating = number_format(4.6 + (($listing->id % 7) * 0.05), 2);
    $reviews = 12 + (($listing->id * 7) % 240);
    $nights = 3;
    $totalPrice = (float) $listing->nightly_price * $nights;
@endphp

<a href="{{ route('site.stays.show', $listing) }}"
   class="listing-card group block">
    <div class="relative overflow-hidden rounded-2xl aspect-[4/5] bg-ink-200">
        <img src="{{ $coverUrl }}"
             alt="{{ $listing->title }}"
             loading="lazy"
             class="listing-card-img absolute inset-0 h-full w-full object-cover">

        <span class="absolute top-3 left-3 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-semibold text-ink-800 shadow-sm">
            ★ <span>编辑精选</span>
        </span>

        <button type="button"
                aria-label="收藏"
                class="absolute top-3 right-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-black/15 backdrop-blur-sm hover:bg-black/30 transition">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                <path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.5-7 10-7 10Z"/>
            </svg>
        </button>

        <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between">
            <span class="rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-700">
                {{ $listing->city ?: '城市' }}
            </span>
        </div>
    </div>

    <div class="mt-3 px-0.5">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-[15px] font-semibold text-ink-900 leading-snug line-clamp-1">
                {{ $listing->title }}
            </h3>
            <div class="flex items-center gap-1 text-[13px] text-ink-700 shrink-0">
                <svg viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5">
                    <path d="m8 1.5 2.1 4.3 4.7.7-3.4 3.3.8 4.7L8 12.3l-4.2 2.2.8-4.7L1.2 6.5l4.7-.7L8 1.5Z"/>
                </svg>
                <span class="font-medium">{{ $rating }}</span>
            </div>
        </div>
        <p class="mt-0.5 text-[13px] text-ink-500 line-clamp-1">
            {{ $listing->address ?: '城市中心' }} · 最多 {{ $listing->max_guests ?? 4 }} 位旅客
        </p>
        <p class="mt-1 text-[13px] text-ink-500">
            5月14日 – 19日 · {{ $reviews }} 条评价
        </p>
        <p class="mt-2 text-[14px] text-ink-900">
            <span class="font-semibold">¥{{ number_format((float) $listing->nightly_price, 0) }}</span>
            <span class="text-ink-500"> / 晚</span>
        </p>
    </div>
</a>

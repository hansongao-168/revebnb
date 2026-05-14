@props([
    'filters' => [],
    'compact' => false,
])

@php
    $destination = $filters['destination'] ?? '';
    $checkIn = $filters['check_in'] ?? '';
    $checkOut = $filters['check_out'] ?? '';
    $guests = $filters['guests'] ?? '';
@endphp

<form method="get"
      action="{{ route('site.stays.index') }}"
      class="search-pill-shadow bg-white rounded-full border border-ink-100 inline-flex items-stretch divide-x divide-ink-100 overflow-hidden {{ $compact ? 'p-0' : 'p-1' }} w-full max-w-3xl">
    <label class="group flex-1 px-6 py-3 hover:bg-cream-100/80 rounded-full cursor-pointer transition">
        <span class="block text-[11px] uppercase tracking-[0.18em] text-ink-500 font-semibold">目的地</span>
        <input type="text"
               name="destination"
               value="{{ $destination }}"
               placeholder="搜索城市、街区或地标"
               class="mt-1 w-full bg-transparent text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none">
    </label>

    <label class="group flex-1 px-6 py-3 hover:bg-cream-100/80 cursor-pointer transition">
        <span class="block text-[11px] uppercase tracking-[0.18em] text-ink-500 font-semibold">入住</span>
        <input type="date"
               name="check_in"
               value="{{ $checkIn }}"
               class="mt-1 w-full bg-transparent text-sm text-ink-900 focus:outline-none">
    </label>

    <label class="group hidden md:block flex-1 px-6 py-3 hover:bg-cream-100/80 cursor-pointer transition">
        <span class="block text-[11px] uppercase tracking-[0.18em] text-ink-500 font-semibold">退房</span>
        <input type="date"
               name="check_out"
               value="{{ $checkOut }}"
               class="mt-1 w-full bg-transparent text-sm text-ink-900 focus:outline-none">
    </label>

    <label class="group hidden lg:block flex-1 px-6 py-3 hover:bg-cream-100/80 cursor-pointer transition">
        <span class="block text-[11px] uppercase tracking-[0.18em] text-ink-500 font-semibold">人数</span>
        <input type="number"
               name="guests"
               min="1"
               max="20"
               value="{{ $guests }}"
               placeholder="添加旅客"
               class="mt-1 w-full bg-transparent text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none">
    </label>

    <div class="flex items-center pr-1 pl-2">
        <button type="submit"
                class="coral-pill inline-flex items-center gap-2 rounded-full px-5 py-3 text-sm font-semibold transition">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" class="h-4 w-4">
                <circle cx="7" cy="7" r="4.5"/>
                <path d="m10.5 10.5 3 3" stroke-linecap="round"/>
            </svg>
            <span>搜索</span>
        </button>
    </div>
</form>

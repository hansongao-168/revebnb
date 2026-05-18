@props([
    'filters' => [],
    'compact' => false,
])

@php
    $destination = $filters['destination'] ?? '';
    $checkIn = $filters['check_in'] ?? '';
    $checkOut = $filters['check_out'] ?? '';
    $adults = (int) ($filters['adults'] ?? 1);
    $children = (int) ($filters['children'] ?? 0);
    $infants = (int) ($filters['infants'] ?? 0);
    $pets = (int) ($filters['pets'] ?? 0);

    $mobileSearchConfig = [
        'destination' => $destination,
        'checkIn' => $checkIn,
        'checkOut' => $checkOut,
        'adults' => $adults,
        'children' => $children,
        'infants' => $infants,
        'pets' => $pets,
    ];
@endphp

{{-- Mobile: Airbnb-style collapsed bar + full-screen search sheet --}}
<div
    x-data="mobileSearch(@js($mobileSearchConfig))"
    class="w-full lg:hidden"
>
    <button
        type="button"
        @click="open()"
        class="mobile-search-trigger search-pill-shadow w-full text-left"
        aria-haspopup="dialog"
        :aria-expanded="isOpen"
    >
        <span class="mobile-search-trigger__icon" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" class="h-4 w-4">
                <circle cx="7" cy="7" r="4.5"/>
                <path d="m10.5 10.5 3 3" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="mobile-search-trigger__copy">
            <span class="mobile-search-trigger__title" x-text="locationSummary"></span>
            <span class="mobile-search-trigger__meta">
                <span x-text="dateSummary"></span>
                <span aria-hidden="true"> · </span>
                <span x-text="guestSummary"></span>
            </span>
        </span>
    </button>

    <template x-teleport="body">
        <div
            x-show="isOpen"
            x-cloak
            class="mobile-search-root"
            style="display: none;"
            role="dialog"
            aria-modal="true"
            aria-label="搜索住宿"
        >
            <div
                class="mobile-search-overlay"
                x-show="isOpen"
                x-transition.opacity
                @click="close()"
            ></div>

            <div
                x-show="isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-full opacity-0"
                class="mobile-search-sheet"
                @click.stop
            >
                <header class="mobile-search-sheet__header">
                    <button
                        type="button"
                        class="mobile-search-sheet__close"
                        @click="close()"
                        aria-label="关闭搜索"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true">
                            <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/>
                        </svg>
                    </button>
                </header>

                <form
                    method="get"
                    action="{{ route('site.stays.index') }}"
                    class="mobile-search-sheet__form"
                >
                    <div class="mobile-search-sheet__scroll">
                        <div class="mobile-search-field-card">
                            <label class="mobile-search-field-card__label" for="mobile-search-destination">地点</label>
                            <input
                                id="mobile-search-destination"
                                type="text"
                                name="destination"
                                x-model="destination"
                                placeholder="搜索目的地"
                                autocomplete="off"
                                class="mobile-search-field-card__input"
                            >
                        </div>

                        <div class="mobile-search-field-card mobile-search-field-card--split">
                            <div class="mobile-search-field-card__half">
                                <label class="mobile-search-field-card__label" for="mobile-search-check-in">入住</label>
                                <input
                                    id="mobile-search-check-in"
                                    type="date"
                                    name="check_in"
                                    x-model="checkIn"
                                    class="mobile-search-field-card__input"
                                >
                            </div>
                            <div class="mobile-search-field-card__divider" aria-hidden="true"></div>
                            <div class="mobile-search-field-card__half">
                                <label class="mobile-search-field-card__label" for="mobile-search-check-out">退房</label>
                                <input
                                    id="mobile-search-check-out"
                                    type="date"
                                    name="check_out"
                                    x-model="checkOut"
                                    class="mobile-search-field-card__input"
                                >
                            </div>
                        </div>

                        <div class="mobile-search-field-card mobile-search-field-card--guests">
                            <p class="mobile-search-field-card__label">客人</p>
                            <div class="mobile-search-guests">
                                @include('components.site.partials.guest-picker-rows-mobile')
                            </div>
                            <input type="hidden" name="adults" :value="adults">
                            <input type="hidden" name="children" :value="children">
                            <input type="hidden" name="infants" :value="infants">
                            <input type="hidden" name="pets" :value="pets">
                        </div>
                    </div>

                    <footer class="mobile-search-sheet__footer">
                        <button type="button" class="mobile-search-sheet__clear" @click="close()">
                            取消
                        </button>
                        <button type="submit" class="mobile-search-sheet__submit coral-pill">
                            搜索
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </template>
</div>

{{-- Desktop: inline search pill --}}
<form
    method="get"
    action="{{ route('site.stays.index') }}"
    class="search-pill-shadow relative z-20 hidden w-full max-w-[52rem] flex-nowrap items-stretch overflow-visible rounded-full border border-ink-100 bg-white lg:flex {{ $compact ? 'p-0' : 'p-1' }}"
>
    <label class="group min-w-0 flex-1 cursor-pointer rounded-full px-5 py-3 transition hover:bg-cream-100/80 lg:px-6">
        <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-ink-500">地点</span>
        <input type="text"
               name="destination"
               value="{{ $destination }}"
               placeholder="搜索目的地"
               class="mt-1 w-full bg-transparent text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none">
    </label>

    <div class="hidden w-px shrink-0 self-stretch bg-ink-100 lg:block" aria-hidden="true"></div>

    <label class="group min-w-0 flex-1 cursor-pointer px-5 py-3 transition hover:bg-cream-100/80 lg:px-6">
        <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-ink-500">入住</span>
        <input type="date"
               name="check_in"
               value="{{ $checkIn }}"
               class="mt-1 w-full bg-transparent text-sm text-ink-900 focus:outline-none">
    </label>

    <div class="hidden w-px shrink-0 self-stretch bg-ink-100 lg:block" aria-hidden="true"></div>

    <label class="group min-w-0 flex-1 cursor-pointer px-5 py-3 transition hover:bg-cream-100/80 lg:px-6">
        <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-ink-500">退房</span>
        <input type="date"
               name="check_out"
               value="{{ $checkOut }}"
               class="mt-1 w-full bg-transparent text-sm text-ink-900 focus:outline-none">
    </label>

    <div class="hidden w-px shrink-0 self-stretch bg-ink-100 lg:block" aria-hidden="true"></div>

    <div class="relative hidden shrink-0 lg:block lg:w-[9.5rem] xl:w-[10.5rem]">
        <x-site.guest-picker
            :adults="$adults"
            :children="$children"
            :infants="$infants"
            :pets="$pets"
            compact
            :use-portal="true"
            summary-style="compact"
        />
    </div>

    <div class="flex shrink-0 items-center py-1 pr-1 pl-2">
        <button type="submit"
                class="coral-pill inline-flex items-center gap-2 rounded-full px-5 py-3 text-sm font-semibold transition">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" class="h-4 w-4" aria-hidden="true">
                <circle cx="7" cy="7" r="4.5"/>
                <path d="m10.5 10.5 3 3" stroke-linecap="round"/>
            </svg>
            <span>搜索</span>
        </button>
    </div>
</form>

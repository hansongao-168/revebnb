@props([
    'adults' => 1,
    'children' => 0,
    'infants' => 0,
    'pets' => 0,
    'compact' => false,
    'limits' => [],
    'usePortal' => false,
    'summaryStyle' => 'full',
    'triggerLabel' => null,
])

@php
    $limits = array_merge([
        'adults' => 16,
        'children' => 16,
        'infants' => 5,
        'pets' => 5,
    ], $limits);

    $label = $triggerLabel ?? ($compact ? '客人' : '人数');
    $portal = $usePortal || $compact;
    $summaryMode = $summaryStyle === 'compact' || $compact ? 'compact' : 'full';
@endphp

<div
    x-data="guestPicker(@js([
        'adults' => (int) $adults,
        'children' => (int) $children,
        'infants' => (int) $infants,
        'pets' => (int) $pets,
        'limits' => $limits,
        'usePortal' => $portal,
        'summaryStyle' => $summaryMode,
    ]))"
    @if (! $portal)
        @click.outside="close()"
    @endif
    class="relative {{ $compact ? 'h-full min-w-0' : 'w-full' }}"
>
    <input type="hidden" name="adults" :value="adults">
    <input type="hidden" name="children" :value="children">
    <input type="hidden" name="infants" :value="infants">
    <input type="hidden" name="pets" :value="pets">

    <button
        type="button"
        x-ref="trigger"
        @click.stop="toggle()"
        class="guest-picker-trigger group flex h-full w-full min-w-[8.5rem] flex-col justify-center text-left {{ $compact ? 'rounded-full px-6 py-3 hover:bg-cream-100/80 transition' : '' }}"
    >
        <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-ink-500">{{ $label }}</span>
        <span class="mt-1 block text-sm font-normal text-ink-900" x-text="displaySummary"></span>
    </button>

    @if ($portal)
        <template x-teleport="body">
            <div
                x-show="open"
                x-transition
                x-cloak
                x-ref="panel"
                @click.outside="close()"
                @click.stop
                :style="popoverStyle"
                class="guest-picker-panel guest-picker-panel--portal"
                style="display: none;"
            >
                @include('components.site.partials.guest-picker-rows')
            </div>
        </template>
    @else
        <div
            x-show="open"
            x-transition
            x-cloak
            x-ref="panel"
            @click.outside="close()"
            @click.stop
            class="guest-picker-panel guest-picker-panel--anchored"
            style="display: none;"
        >
            @include('components.site.partials.guest-picker-rows')
        </div>
    @endif
</div>

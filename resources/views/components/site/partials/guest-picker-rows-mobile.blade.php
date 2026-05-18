@foreach ([
    ['key' => 'adults', 'label' => '成人', 'hint' => '13 岁及以上', 'min' => 1],
    ['key' => 'children', 'label' => '儿童', 'hint' => '2–12 岁', 'min' => 0],
    ['key' => 'infants', 'label' => '婴儿', 'hint' => '2 岁以下', 'min' => 0],
    ['key' => 'pets', 'label' => '宠物', 'hint' => '是否携带宠物', 'min' => 0],
] as $row)
    <div class="guest-picker-row {{ ! $loop->last ? 'guest-picker-row--bordered' : '' }}">
        <div class="guest-picker-row__copy">
            <p class="guest-picker-row__title">{{ $row['label'] }}</p>
            <p class="guest-picker-row__hint">{{ $row['hint'] }}</p>
        </div>
        <div class="guest-picker-stepper" role="group" aria-label="{{ $row['label'] }}">
            <button
                type="button"
                class="guest-picker-stepper__btn"
                @click="decrement('{{ $row['key'] }}')"
                :disabled="{{ $row['key'] }} <= {{ $row['min'] }}"
            >
                <span class="sr-only">减少{{ $row['label'] }}</span>
                <svg viewBox="0 0 12 12" class="h-3 w-3" aria-hidden="true">
                    <path d="M2 6h8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
            </button>
            <span class="guest-picker-stepper__value" x-text="{{ $row['key'] }}"></span>
            <button
                type="button"
                class="guest-picker-stepper__btn"
                @click="increment('{{ $row['key'] }}')"
                :disabled="{{ $row['key'] }} >= limits.{{ $row['key'] }}"
            >
                <span class="sr-only">增加{{ $row['label'] }}</span>
                <svg viewBox="0 0 12 12" class="h-3 w-3" aria-hidden="true">
                    <path d="M6 2v8M2 6h8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>
@endforeach

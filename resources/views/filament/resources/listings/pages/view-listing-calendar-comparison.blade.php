<x-filament-panels::page>
    @php
        $summary = $comparison['summary'] ?? ['external_only' => 0, 'local_only' => 0, 'overlap' => 0];
        $days = $comparison['days'] ?? [];
        $events = $comparison['external_events'] ?? [];
        $weekdays = ['一', '二', '三', '四', '五', '六', '日'];
        $leadingEmpty = \Carbon\Carbon::createFromFormat('Y-m', $month)->dayOfWeekIso - 1;
        $totalDaySlots = $leadingEmpty + count($days);
        $trailingEmpty = $totalDaySlots % 7 === 0 ? 0 : 7 - ($totalDaySlots % 7);
    @endphp

    <style>
        .listing-calendar-comparison__summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (max-width: 640px) {
            .listing-calendar-comparison__summary {
                grid-template-columns: 1fr;
            }
        }

        .listing-calendar-comparison__month {
            width: 100%;
            max-width: 42rem;
        }

        .listing-calendar-comparison__grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 0.25rem;
        }

        .listing-calendar-comparison__weekday {
            padding: 0.35rem 0;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgb(107 114 128);
        }

        .dark .listing-calendar-comparison__weekday {
            color: rgb(156 163 175);
        }

        .listing-calendar-comparison__cell {
            min-height: 3rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(229 231 235);
            padding: 0.35rem;
            text-align: center;
            font-size: 0.75rem;
            line-height: 1.2;
        }

        .dark .listing-calendar-comparison__cell {
            border-color: rgb(55 65 81);
        }

        .listing-calendar-comparison__cell--empty {
            border-color: transparent;
            background: transparent;
        }

        .listing-calendar-comparison__cell--default {
            background: rgb(255 255 255);
        }

        .dark .listing-calendar-comparison__cell--default {
            background: rgb(17 24 39);
        }

        .listing-calendar-comparison__cell--external {
            border-color: rgb(252 211 77);
            background: rgb(255 251 235);
        }

        .dark .listing-calendar-comparison__cell--external {
            border-color: rgb(180 83 9);
            background: rgb(69 26 3);
        }

        .listing-calendar-comparison__cell--booking {
            border-color: rgb(253 164 175);
            background: rgb(255 241 242);
        }

        .dark .listing-calendar-comparison__cell--booking {
            border-color: rgb(190 18 60);
            background: rgb(76 5 25);
        }

        .listing-calendar-comparison__cell--block {
            border-color: rgb(203 213 225);
            background: rgb(241 245 249);
        }

        .dark .listing-calendar-comparison__cell--block {
            border-color: rgb(100 116 139);
            background: rgb(15 23 42);
        }

        .listing-calendar-comparison__cell--overlap {
            border-color: rgb(167 139 250);
            background: rgb(237 233 254);
        }

        .dark .listing-calendar-comparison__cell--overlap {
            border-color: rgb(124 58 237);
            background: rgb(46 16 101);
        }

        .listing-calendar-comparison__day-num {
            font-weight: 600;
        }

        .listing-calendar-comparison__legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: rgb(75 85 99);
        }

        .dark .listing-calendar-comparison__legend {
            color: rgb(156 163 175);
        }

        .listing-calendar-comparison__legend-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .listing-calendar-comparison__legend-swatch {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 0.125rem;
            flex-shrink: 0;
        }

        .listing-calendar-comparison__empty-hint {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: rgb(107 114 128);
        }

        .listing-calendar-comparison__events-wrap {
            overflow-x: auto;
        }

        .listing-calendar-comparison__events-table {
            width: 100%;
            min-width: 36rem;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .listing-calendar-comparison__events-table th,
        .listing-calendar-comparison__events-table td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid rgb(229 231 235);
            vertical-align: top;
        }

        .dark .listing-calendar-comparison__events-table th,
        .dark .listing-calendar-comparison__events-table td {
            border-bottom-color: rgb(55 65 81);
        }

        .listing-calendar-comparison__month-picker {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .listing-calendar-comparison__month-picker label {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .listing-calendar-comparison__month-picker input {
            border-radius: 0.5rem;
            border: 1px solid rgb(209 213 219);
            padding: 0.35rem 0.5rem;
            font-size: 0.875rem;
        }

        .dark .listing-calendar-comparison__month-picker input {
            border-color: rgb(75 85 99);
            background: rgb(17 24 39);
            color: rgb(243 244 246);
        }
    </style>

    <div class="space-y-6">
        @if (Filament\Facades\Filament::getCurrentPanel()?->getId() === 'admin')
            <p class="text-sm text-gray-600 dark:text-gray-400">
                尚未配置外部日历时，请点击页头
                <strong>获取 Airbnb iCal 链接</strong>
                查看操作说明，并在编辑页「外部日历」中粘贴 URL 后同步。
            </p>
        @elseif (in_array(Filament\Facades\Filament::getCurrentPanel()?->getId(), ['landlord', 'tenant'], true))
            <p class="text-sm text-gray-600 dark:text-gray-400">
                外部 ICS 订阅由平台在后台配置与同步；此页仅供查看占用对比。
            </p>
        @endif

        <form method="get" class="listing-calendar-comparison__month-picker">
            <label for="month">月份</label>
            <input
                id="month"
                name="month"
                type="month"
                value="{{ $month }}"
                onchange="this.form.submit()"
            />
        </form>

        <div class="listing-calendar-comparison__summary">
            <x-filament::section>
                <x-slot name="heading">仅外部占用</x-slot>
                <p class="text-2xl font-semibold text-amber-600">{{ $summary['external_only'] }} 晚</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">仅本地占用</x-slot>
                <p class="text-2xl font-semibold text-sky-600">{{ $summary['local_only'] }} 晚</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">重叠</x-slot>
                <p class="text-2xl font-semibold text-violet-600">{{ $summary['overlap'] }} 晚</p>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">图例</x-slot>
            <div class="listing-calendar-comparison__legend">
                <span class="listing-calendar-comparison__legend-item">
                    <span class="listing-calendar-comparison__legend-swatch" style="background: rgb(251 191 36);"></span>
                    外部 ICS
                </span>
                <span class="listing-calendar-comparison__legend-item">
                    <span class="listing-calendar-comparison__legend-swatch" style="background: rgb(251 113 133);"></span>
                    已确认订单
                </span>
                <span class="listing-calendar-comparison__legend-item">
                    <span class="listing-calendar-comparison__legend-swatch" style="background: rgb(148 163 184);"></span>
                    手动不可租
                </span>
                <span class="listing-calendar-comparison__legend-item">
                    <span class="listing-calendar-comparison__legend-swatch" style="background: rgb(139 92 246);"></span>
                    重叠
                </span>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ $month }} 月历</x-slot>
            <div class="listing-calendar-comparison__month">
                <div class="listing-calendar-comparison__grid" role="grid" aria-label="{{ $month }} 月历">
                    @foreach ($weekdays as $weekday)
                        <div class="listing-calendar-comparison__weekday" role="columnheader">{{ $weekday }}</div>
                    @endforeach

                    @for ($i = 0; $i < $leadingEmpty; $i++)
                        <div class="listing-calendar-comparison__cell listing-calendar-comparison__cell--empty" aria-hidden="true"></div>
                    @endfor

                    @foreach ($days as $day)
                        @php
                            $tone = 'default';
                            if ($day['overlap']) {
                                $tone = 'overlap';
                            } elseif ($day['external']) {
                                $tone = 'external';
                            } elseif ($day['booking']) {
                                $tone = 'booking';
                            } elseif ($day['block']) {
                                $tone = 'block';
                            }
                        @endphp
                        <div
                            class="listing-calendar-comparison__cell listing-calendar-comparison__cell--{{ $tone }}"
                            role="gridcell"
                            title="{{ $day['date'] }}"
                        >
                            <span class="listing-calendar-comparison__day-num">{{ $day['day'] }}</span>
                        </div>
                    @endforeach

                    @for ($i = 0; $i < $trailingEmpty; $i++)
                        <div class="listing-calendar-comparison__cell listing-calendar-comparison__cell--empty" aria-hidden="true"></div>
                    @endfor
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">当月外部事件</x-slot>
            @if ($events === [])
                <div class="listing-calendar-comparison__empty-hint">
                    @if (Filament\Facades\Filament::getCurrentPanel()?->getId() === 'admin')
                        <p>暂无外部日历事件。</p>
                        <p>
                            <x-filament::link
                                :href="route('docs.ics-external-calendar').'#airbnb-ical'"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                获取 Airbnb iCal 链接
                            </x-filament::link>
                            <span> · </span>
                            <x-filament::link
                                :href="$this->getResource()::getUrl('edit', ['record' => $this->getRecord()])"
                            >
                                编辑房源 → 外部日历
                            </x-filament::link>
                        </p>
                        <p>粘贴 URL 后执行「立即同步」。</p>
                    @else
                        <p>暂无外部日历事件，请联系平台运营配置并同步 ICS。</p>
                    @endif
                </div>
            @else
                <div class="listing-calendar-comparison__events-wrap">
                    <table class="listing-calendar-comparison__events-table">
                        <thead>
                            <tr>
                                <th>来源</th>
                                <th>摘要</th>
                                <th>开始</th>
                                <th>结束</th>
                                <th>占用夜</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $event)
                                <tr>
                                    <td>{{ $event['feed_label'] }}</td>
                                    <td>{{ $event['summary'] ?? '—' }}</td>
                                    <td>{{ $event['starts_at'] }}</td>
                                    <td>{{ $event['ends_at'] }}</td>
                                    <td>{{ implode(', ', $event['blocked_nights']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>

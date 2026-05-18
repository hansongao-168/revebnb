<x-filament-panels::page>
    @php
        $summary = $comparison['summary'] ?? ['external_only' => 0, 'local_only' => 0, 'overlap' => 0];
        $days = $comparison['days'] ?? [];
        $events = $comparison['external_events'] ?? [];
    @endphp

    <div class="space-y-6">
        @if (Filament\Facades\Filament::getCurrentPanel()?->getId() === 'landlord')
            <p class="text-sm text-gray-600 dark:text-gray-400">
                外部 ICS 订阅由平台在后台配置与同步；此页仅供查看占用对比。
            </p>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button
                tag="a"
                :href="$this->getResource()::getUrl('edit', ['record' => $this->getRecord()])"
                color="gray"
                icon="heroicon-o-arrow-left"
            >
                返回编辑房源
            </x-filament::button>

            <form method="get" class="flex items-center gap-2">
                <label for="month" class="text-sm font-medium text-gray-700 dark:text-gray-300">月份</label>
                <input
                    id="month"
                    name="month"
                    type="month"
                    value="{{ $month }}"
                    class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900"
                    onchange="this.form.submit()"
                />
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
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
            <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <span class="h-3 w-3 rounded bg-amber-400"></span> 外部 ICS
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="h-3 w-3 rounded bg-rose-400"></span> 已确认订单
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="h-3 w-3 rounded bg-slate-400"></span> 手动不可租
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="h-3 w-3 rounded bg-violet-500"></span> 重叠
                </span>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ $month }} 月历</x-slot>
            <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-500">
                @foreach (['一', '二', '三', '四', '五', '六', '日'] as $weekday)
                    <div class="py-1">{{ $weekday }}</div>
                @endforeach
            </div>
            <div class="mt-1 grid grid-cols-7 gap-1">
                @php
                    $firstDow = \Carbon\Carbon::createFromFormat('Y-m', $month)->dayOfWeekIso;
                    $padding = $firstDow - 1;
                @endphp
                @for ($i = 0; $i < $padding; $i++)
                    <div class="min-h-12 rounded-lg bg-transparent"></div>
                @endfor
                @foreach ($days as $day)
                    @php
                        $classes = 'min-h-12 rounded-lg border p-1 text-xs ';
                        if ($day['overlap']) {
                            $classes .= 'border-violet-400 bg-violet-100 dark:bg-violet-950';
                        } elseif ($day['external']) {
                            $classes .= 'border-amber-300 bg-amber-50 dark:bg-amber-950';
                        } elseif ($day['booking']) {
                            $classes .= 'border-rose-300 bg-rose-50 dark:bg-rose-950';
                        } elseif ($day['block']) {
                            $classes .= 'border-slate-300 bg-slate-100 dark:bg-slate-900';
                        } else {
                            $classes .= 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900';
                        }
                    @endphp
                    <div class="{{ $classes }}">
                        <div class="font-semibold">{{ $day['day'] }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">当月外部事件</x-slot>
            @if ($events === [])
                <p class="text-sm text-gray-500">暂无外部日历事件，请先配置并同步 ICS。</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-2 py-2">来源</th>
                                <th class="px-2 py-2">摘要</th>
                                <th class="px-2 py-2">开始</th>
                                <th class="px-2 py-2">结束</th>
                                <th class="px-2 py-2">占用夜</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $event)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-2 py-2">{{ $event['feed_label'] }}</td>
                                    <td class="px-2 py-2">{{ $event['summary'] ?? '—' }}</td>
                                    <td class="px-2 py-2">{{ $event['starts_at'] }}</td>
                                    <td class="px-2 py-2">{{ $event['ends_at'] }}</td>
                                    <td class="px-2 py-2">{{ implode(', ', $event['blocked_nights']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>

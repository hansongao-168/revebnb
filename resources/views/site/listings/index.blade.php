<x-layouts.site :navActive="'stays'">
    <x-slot:title>住宿 · Revebnb</x-slot:title>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div aria-hidden="true"
             class="pointer-events-none absolute -top-32 -left-32 h-[28rem] w-[28rem] rounded-full"
             style="background: radial-gradient(closest-side, rgba(255,56,92,0.18), transparent 70%);"></div>
        <div aria-hidden="true"
             class="pointer-events-none absolute -top-20 right-0 h-[26rem] w-[26rem] rounded-full"
             style="background: radial-gradient(closest-side, rgba(255,200,120,0.22), transparent 70%);"></div>

        <div class="site-shell pt-14 md:pt-20 pb-12 md:pb-16 relative">
            <p class="text-[11px] uppercase tracking-[0.4em] text-coral-600 font-semibold">REVEBNB · CITY STAYS</p>
            <h1 class="display-hero mt-5 text-5xl md:text-7xl lg:text-[5.5rem] text-ink-900 max-w-4xl">
                城市里的<em>另一段</em>停留<br>
                <span class="text-ink-500 font-medium tracking-tight">为编辑级旅客精选的住所。</span>
            </h1>

            <div class="mt-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                <p class="text-base md:text-lg text-ink-600 max-w-xl leading-relaxed">
                    从设计型公寓到山海小屋，每一处住所都经过严格甄选——明亮、安静、并随手可触地写下印象。
                </p>
                <div class="flex items-center gap-2 text-sm text-ink-500">
                    <span class="inline-flex h-2 w-2 rounded-full bg-coral-500 animate-pulse"></span>
                    <span>共 <span class="font-semibold text-ink-900">{{ $listings->total() }}</span> 处住所正在出租</span>
                </div>
            </div>

            <div class="mt-10">
                <x-site.search-bar :filters="$filters" />
            </div>
        </div>
    </section>

    <x-site.category-strip :active="$filters['category'] ?? null" :filters="$filters" />

    {{-- Grid --}}
    <section class="site-shell pt-8 md:pt-10">
        @if ($listings->isEmpty())
            <div class="rounded-3xl border border-dashed border-ink-200 bg-white p-12 text-center">
                <p class="text-lg font-semibold text-ink-900">暂无匹配的住所</p>
                <p class="mt-2 text-sm text-ink-500">试着放宽时间或换一座城市，下一段旅程在等你。</p>
                <a href="{{ route('site.stays.index') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-full border border-ink-200 px-5 py-2 text-sm font-medium text-ink-800 hover:bg-ink-100 transition">
                    重置筛选
                </a>
            </div>
        @else
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl md:text-3xl font-semibold text-ink-900 tracking-tight">
                        @if (! empty($filters['destination']))
                            「{{ $filters['destination'] }}」附近 · {{ $listings->total() }} 处住所
                        @else
                            今日推荐
                        @endif
                    </h2>
                    <p class="mt-1 text-sm text-ink-500">价格含税前 · 实际费用以预订为准</p>
                </div>

                <div class="hidden md:flex items-center gap-2 text-sm text-ink-600">
                    <span class="text-ink-400">排序</span>
                    <button type="button" class="rounded-full border border-ink-200 px-4 py-2 hover:border-ink-400 transition">推荐</button>
                    <button type="button" class="rounded-full border border-ink-200 px-4 py-2 hover:border-ink-400 transition">价格 ↑</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-6 gap-y-12">
                @foreach ($listings as $listing)
                    <x-site.listing-card :listing="$listing" />
                @endforeach
            </div>

            <div class="mt-14 flex justify-center">
                {{ $listings->onEachSide(1)->links() }}
            </div>
        @endif
    </section>

    {{-- Editorial strip --}}
    <section class="site-shell mt-24">
        <div class="grid md:grid-cols-3 gap-6 md:gap-8">
            @foreach ([
                ['kicker' => '编辑笔记 01', 'title' => '一座城市的清晨', 'desc' => '我们花了三个月，把上海弄堂里的老房子重新做成了写作角。'],
                ['kicker' => '编辑笔记 02', 'title' => '海边的下一站', 'desc' => '从大理到鼓浪屿，10 处住所专为漫长假期而生。'],
                ['kicker' => '编辑笔记 03', 'title' => '设计师的客厅', 'desc' => '与本地设计工作室合作，重新定义旅居的居家美学。'],
            ] as $i => $note)
                <article class="group relative overflow-hidden rounded-2xl bg-white ring-ink p-7 flex flex-col gap-3 hover:-translate-y-0.5 transition">
                    <span class="text-[11px] uppercase tracking-[0.32em] text-coral-600 font-semibold">{{ $note['kicker'] }}</span>
                    <h3 class="text-2xl font-semibold text-ink-900 tracking-tight">{{ $note['title'] }}</h3>
                    <p class="text-sm text-ink-500 leading-relaxed flex-1">{{ $note['desc'] }}</p>
                    <span class="inline-flex items-center gap-2 text-sm font-medium text-ink-900 mt-2 group-hover:gap-3 transition-all">
                        阅读全文
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5">
                            <path d="m6 3 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.site>

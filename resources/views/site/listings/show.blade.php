<x-layouts.site :navActive="'stays'">
    <x-slot:title>{{ $listing->title }} · Revebnb</x-slot:title>

    @php
        $images = $listing->images;
        $cover = $images->firstWhere('is_cover', true) ?? $images->first();
        $coverUrl = \App\Support\ListingImageUrl::url($cover?->path, (string) $listing->id);
        $gallery = $images->where('id', '!=', $cover?->id)->take(4)->values();
        $rating = number_format(4.7 + (($listing->id % 6) * 0.05), 2);
        $reviews = 28 + (($listing->id * 11) % 320);
    @endphp

    <section class="site-shell pt-8 md:pt-10">
        <nav class="text-xs text-ink-500 mb-4 flex items-center gap-2">
            <a href="{{ route('site.stays.index') }}" class="hover:text-ink-900">住宿</a>
            <span class="text-ink-300">/</span>
            <span class="text-ink-700">{{ $listing->city ?: '城市' }}</span>
            <span class="text-ink-300">/</span>
            <span class="text-ink-700 truncate max-w-[18rem]">{{ $listing->title }}</span>
        </nav>

        <header class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-semibold text-ink-900 tracking-tight max-w-3xl">
                    {{ $listing->title }}
                </h1>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-600">
                    <span class="inline-flex items-center gap-1 font-semibold text-ink-900">
                        <svg viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5">
                            <path d="m8 1.5 2.1 4.3 4.7.7-3.4 3.3.8 4.7L8 12.3l-4.2 2.2.8-4.7L1.2 6.5l4.7-.7L8 1.5Z"/>
                        </svg>
                        {{ $rating }}
                    </span>
                    <a href="#reviews" class="underline underline-offset-4 hover:text-ink-900">{{ $reviews }} 条评价</a>
                    <span>·</span>
                    <span>超赞房东 · 编辑精选</span>
                    <span>·</span>
                    <span>{{ $listing->city }}{{ $listing->address ? ' · '.$listing->address : '' }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3 text-sm text-ink-700">
                <button type="button" class="inline-flex items-center gap-2 rounded-full px-3 py-2 hover:bg-ink-100 transition">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M4 12v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8M16 6l-4-4-4 4M12 2v14"/>
                    </svg>
                    <span class="underline underline-offset-4">分享</span>
                </button>
                <button type="button" class="inline-flex items-center gap-2 rounded-full px-3 py-2 hover:bg-ink-100 transition">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.5-7 10-7 10Z"/>
                    </svg>
                    <span class="underline underline-offset-4">收藏</span>
                </button>
            </div>
        </header>

        {{-- Photo grid --}}
        <div class="grid grid-cols-1 md:grid-cols-4 md:grid-rows-2 gap-2 rounded-3xl overflow-hidden h-[36rem]">
            <div class="md:col-span-2 md:row-span-2 relative bg-ink-200">
                <img src="{{ $coverUrl }}" alt="{{ $listing->title }}" class="absolute inset-0 h-full w-full object-cover">
            </div>
            @for ($i = 0; $i < 4; $i++)
                @php
                    $img = $gallery[$i] ?? null;
                    $url = $img
                        ? \App\Support\ListingImageUrl::url($img->path, $listing->id.'-'.($i + 1))
                        : \App\Support\ListingImageUrl::placeholder($listing->id.'-'.($i + 9), 800, 700);
                @endphp
                <div class="hidden md:block relative bg-ink-200">
                    <img src="{{ $url }}" alt="{{ $listing->title }} 第 {{ $i + 2 }} 张" class="absolute inset-0 h-full w-full object-cover">
                    @if ($i === 3)
                        <button type="button"
                                class="absolute right-3 bottom-3 inline-flex items-center gap-2 rounded-full bg-white/95 px-3 py-1.5 text-xs font-semibold text-ink-900 ring-ink hover:bg-white transition">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5">
                                <rect x="1" y="1" width="14" height="14" rx="2"/>
                                <path d="m1 11 4-4 4 4 2-2 4 4" stroke-linejoin="round"/>
                            </svg>
                            查看全部图片
                        </button>
                    @endif
                </div>
            @endfor
        </div>
    </section>

    {{-- Content + sidebar --}}
    <section class="site-shell mt-10 md:mt-14 grid grid-cols-1 lg:grid-cols-3 gap-12 lg:gap-16">
        <div class="lg:col-span-2">
            <div class="flex items-start justify-between border-b border-ink-100 pb-8">
                <div>
                    <h2 class="text-2xl font-semibold text-ink-900 tracking-tight">
                        @if ($listing->landlord)
                            房东 {{ $listing->landlord->name ?? '匿名' }} · 整套住所
                        @else
                            整套住所 · 编辑精选
                        @endif
                    </h2>
                    <p class="mt-2 text-sm text-ink-500">
                        最多 {{ $listing->max_guests ?? 4 }} 位旅客 · 1 间卧室 · 2 张床 · 1 间浴室
                    </p>
                </div>
                <div class="hidden md:flex h-14 w-14 items-center justify-center rounded-full bg-ink-100 text-ink-800 font-semibold">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($listing->landlord->name ?? '编辑', 0, 1)) }}
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-8 border-b border-ink-100">
                @foreach ([
                    ['icon' => 'M3 9h18v12H3zM5 9V5a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v4', 'title' => '即时确认', 'desc' => '提交即可瞬时收到房东确认'],
                    ['icon' => 'M3 21V3l18 9-18 9Z', 'title' => '入住自助', 'desc' => '使用智能门锁，无需当面交接'],
                    ['icon' => 'M5 4h14v16H5zM9 8h6M9 12h6M9 16h4', 'title' => '编辑认证', 'desc' => '我们的编辑团队亲身体验并撰写说明'],
                ] as $perk)
                    <div class="flex gap-4">
                        <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-full bg-cream-100 text-ink-900">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                                 stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="{{ $perk['icon'] }}"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-base font-semibold text-ink-900">{{ $perk['title'] }}</p>
                            <p class="mt-0.5 text-sm text-ink-500">{{ $perk['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="py-8 border-b border-ink-100 prose-sm max-w-none">
                <h3 class="text-xl font-semibold text-ink-900 tracking-tight">关于这处住所</h3>
                <p class="mt-3 text-[15px] leading-7 text-ink-700 whitespace-pre-line">
                    {{ $listing->description ?: '主人精心打造的城市住所，为旅居者准备了一处可以慢下来的空间。早晨有阳光，傍晚有书。' }}
                </p>
            </div>

            @if ($listing->guest_info_html)
                <div class="py-8 border-b border-ink-100">
                    <h3 class="text-xl font-semibold text-ink-900 tracking-tight mb-4">入住须知</h3>
                    <div class="prose prose-sm max-w-none text-ink-700 [&_a]:text-coral-600">
                        {!! $listing->guest_info_html !!}
                    </div>
                </div>
            @endif

            <div class="py-8 border-b border-ink-100">
                <h3 class="text-xl font-semibold text-ink-900 tracking-tight mb-4">设施与服务</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm text-ink-700">
                    @foreach (['极速 Wi-Fi', '专属厨房', '洗衣机', '空调 / 暖气', '电梯入户', '小区门禁', '智能门锁', '工作书桌', '行李寄存', '免费茶水'] as $facility)
                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-coral-500"></span>
                            <span>{{ $facility }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Booking card --}}
        <aside class="lg:col-span-1">
            <div class="lg:sticky lg:top-32">
                @if ($errors->any())
                    <div class="mb-4 rounded-2xl bg-coral-500/5 border border-coral-500/30 p-4 text-sm text-coral-700">
                        <p class="font-semibold mb-1">请检查以下信息：</p>
                        <ul class="list-disc pl-5 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $bookingCalendarConfig = [
                        'slug' => $listing->slug,
                        'nightlyPrice' => (float) $listing->nightly_price,
                        'minNights' => (int) $listing->min_nights,
                        'maxGuests' => (int) ($listing->max_guests ?? 10),
                        'initialCheckIn' => old('check_in', $defaultCheckIn),
                        'initialCheckOut' => old('check_out', $defaultCheckOut),
                        'initialMonth' => \Illuminate\Support\Carbon::parse(old('check_in', $defaultCheckIn))->format('Y-m'),
                    ];
                @endphp
                <div class="price-card rounded-2xl bg-white p-6 border border-ink-100"
                     x-data="revebnbStayBooking({{ \Illuminate\Support\Js::from($bookingCalendarConfig) }})">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <span class="text-2xl font-semibold text-ink-900">¥{{ number_format((float) $listing->nightly_price, 0) }}</span>
                            <span class="text-sm text-ink-500"> / 晚</span>
                        </div>
                        <div class="flex items-center gap-1 text-sm text-ink-700">
                            <svg viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5">
                                <path d="m8 1.5 2.1 4.3 4.7.7-3.4 3.3.8 4.7L8 12.3l-4.2 2.2.8-4.7L1.2 6.5l4.7-.7L8 1.5Z"/>
                            </svg>
                            <span class="font-medium">{{ $rating }}</span>
                            <span class="text-ink-400">·</span>
                            <a href="#reviews" class="underline underline-offset-2 text-ink-500">{{ $reviews }} 条</a>
                        </div>
                    </div>

                    <form method="post" action="{{ route('site.bookings.store', $listing) }}" class="mt-5">
                        @csrf

                        <input type="hidden" name="check_in" :value="checkIn">
                        <input type="hidden" name="check_out" :value="checkOut">

                        <div class="rounded-xl border border-ink-200 p-3">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <button type="button" @click="prevMonth()"
                                        class="rounded-full border border-ink-200 px-2 py-1 text-xs font-medium text-ink-700 hover:bg-ink-100 transition">
                                    ‹
                                </button>
                                <span class="text-sm font-semibold text-ink-900" x-text="monthLabel()"></span>
                                <button type="button" @click="nextMonth()"
                                        class="rounded-full border border-ink-200 px-2 py-1 text-xs font-medium text-ink-700 hover:bg-ink-100 transition">
                                    ›
                                </button>
                            </div>
                            <p class="text-[11px] text-ink-500 mb-2" x-show="loading" x-cloak>加载可订日期…</p>
                            <div class="grid grid-cols-7 gap-0.5 text-center text-[10px] uppercase tracking-wide text-ink-400 mb-1">
                                <span>一</span><span>二</span><span>三</span><span>四</span><span>五</span><span>六</span><span>日</span>
                            </div>
                            <div class="grid grid-cols-7 gap-0.5 text-xs">
                                <template x-for="(cell, idx) in cells" :key="cell.empty ? 'e-' + idx : cell.dateStr">
                                    <div>
                                        <template x-if="cell.empty">
                                            <span class="block h-8"></span>
                                        </template>
                                        <template x-if="!cell.empty">
                                            <button type="button"
                                                    @click="selectDay(cell.dateStr)"
                                                    class="h-8 w-full rounded-lg flex items-center justify-center font-medium transition"
                                                    :class="{
                                                        'text-ink-300 cursor-not-allowed': cell.past || cell.blocked,
                                                        'bg-coral-500 text-white': cell.isCheckIn || cell.isCheckOut,
                                                        'bg-cream-100 text-ink-900': cell.inStayRange && !cell.isCheckIn && !cell.isCheckOut,
                                                        'text-ink-800 hover:bg-ink-100': !cell.past && !cell.blocked && !cell.inStayRange && !cell.isCheckIn && !cell.isCheckOut,
                                                        'line-through text-ink-300': cell.blocked && !cell.past
                                                    }"
                                                    :disabled="cell.past || cell.blocked"
                                                    x-text="cell.dayNum">
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <p class="mt-2 text-[11px] text-ink-500">
                                已选：<span class="font-medium text-ink-800" x-text="checkIn || '—'"></span>
                                至 <span class="font-medium text-ink-800" x-text="checkOut || '—'"></span>
                                <span class="text-ink-400">（灰色为不可订）</span>
                            </p>
                        </div>

                        <label class="block border border-ink-200 rounded-xl mt-3 px-3 py-2.5 hover:bg-cream-100 transition cursor-pointer">
                            <span class="block text-[10px] uppercase tracking-[0.18em] text-ink-500 font-semibold">旅客</span>
                            <input type="number" name="guests"
                                   min="1" max="{{ $listing->max_guests ?? 10 }}"
                                   value="{{ old('guests', 2) }}"
                                   class="mt-1 w-full bg-transparent text-sm text-ink-900 focus:outline-none">
                        </label>

                        <div class="mt-4 grid grid-cols-1 gap-3">
                            <label class="block">
                                <span class="block text-[10px] uppercase tracking-[0.18em] text-ink-500 font-semibold">姓名</span>
                                <input type="text" name="guest_name"
                                       value="{{ old('guest_name') }}"
                                       required
                                       placeholder="您的姓名"
                                       class="mt-1 w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:border-ink-700 transition">
                            </label>
                            <label class="block">
                                <span class="block text-[10px] uppercase tracking-[0.18em] text-ink-500 font-semibold">邮箱（选填）</span>
                                <input type="email" name="guest_email"
                                       value="{{ old('guest_email') }}"
                                       placeholder="用于接收订单链接"
                                       class="mt-1 w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:border-ink-700 transition">
                            </label>
                            <label class="block">
                                <span class="block text-[10px] uppercase tracking-[0.18em] text-ink-500 font-semibold">备注（选填）</span>
                                <textarea name="notes" rows="2"
                                          placeholder="抵达时间、特殊请求…"
                                          class="mt-1 w-full rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:border-ink-700 transition">{{ old('notes') }}</textarea>
                            </label>
                        </div>

                        <button type="submit"
                                class="coral-pill mt-5 w-full inline-flex items-center justify-center rounded-2xl px-5 py-3.5 text-base font-semibold transition">
                            发送预订请求
                        </button>

                        <p class="mt-3 text-center text-xs text-ink-500">
                            提交后房东将在 24 小时内确认，期间不会扣款。
                        </p>
                    </form>

                    <div class="mt-6 border-t border-ink-100 pt-4 space-y-2 text-sm text-ink-700" x-show="nightsCount() > 0" x-cloak>
                        <div class="flex items-center justify-between">
                            <span>房费（估算）</span>
                            <span>¥<span x-text="Math.round(roomSubtotal()).toLocaleString()"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-ink-500">
                            <span x-text="'¥' + Math.round(nightlyPrice).toLocaleString() + ' × ' + nightsCount() + ' 晚'"></span>
                        </div>
                        <p class="text-xs text-ink-500 pt-2 border-t border-ink-100">
                            清洁费、服务费等以房东最终确认为准；此处仅为房费估算。
                        </p>
                    </div>
                </div>

                <p class="mt-4 text-center text-xs text-ink-500">
                    住所最少入住 {{ $listing->min_nights }} 晚
                </p>
            </div>
        </aside>
    </section>

    {{-- Reviews placeholder --}}
    <section id="reviews" class="site-shell mt-20 border-t border-ink-100 pt-12">
        <div class="flex items-center gap-3 mb-8">
            <svg viewBox="0 0 16 16" fill="currentColor" class="h-5 w-5 text-ink-900">
                <path d="m8 1.5 2.1 4.3 4.7.7-3.4 3.3.8 4.7L8 12.3l-4.2 2.2.8-4.7L1.2 6.5l4.7-.7L8 1.5Z"/>
            </svg>
            <h2 class="text-2xl md:text-3xl font-semibold text-ink-900 tracking-tight">
                {{ $rating }} · {{ $reviews }} 条评价
            </h2>
        </div>

        <div class="grid md:grid-cols-2 gap-x-12 gap-y-10">
            @foreach ([
                ['name' => 'Mia', 'when' => '2 周前', 'text' => '一进门就觉得放松，主人留的手写卡片让我决定下次还来。'],
                ['name' => 'Junjie', 'when' => '上个月', 'text' => '光线很好，做饭也方便。社区安静，离地铁四百米。'],
                ['name' => 'Yi', 'when' => '上个月', 'text' => '细节真的太用心，连咖啡豆都是本地烘焙店。'],
                ['name' => 'Ada', 'when' => '两个月前', 'text' => '我把它当成了一个临时办公室，再也不想去咖啡馆赶稿了。'],
            ] as $rev)
                <article class="space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-cream-100 text-ink-800 font-semibold">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($rev['name'], 0, 1)) }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink-900">{{ $rev['name'] }}</p>
                            <p class="text-xs text-ink-500">{{ $rev['when'] }}</p>
                        </div>
                    </div>
                    <p class="text-[15px] leading-7 text-ink-700">{{ $rev['text'] }}</p>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.site>

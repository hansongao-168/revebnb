<x-layouts.site :navActive="'bookings'">
    <x-slot:title>订单详情 · Revebnb</x-slot:title>

    @php
        $statusLabel = match ($booking->status) {
            \App\Enums\BookingStatus::Draft => '草稿',
            \App\Enums\BookingStatus::Pending => '待确认',
            \App\Enums\BookingStatus::Confirmed => '已确认',
            \App\Enums\BookingStatus::Cancelled => '已取消',
        };
    @endphp

    <section class="site-shell py-12 md:py-16">
        <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-3xl bg-white p-8 md:p-10 ring-ink">
                <p class="text-[11px] uppercase tracking-[0.4em] text-coral-600 font-semibold">Booking Detail</p>
                <h1 class="mt-4 text-3xl md:text-4xl font-semibold text-ink-900 tracking-tight">订单详情</h1>
                <p class="mt-3 text-sm leading-6 text-ink-600">
                    当前订单状态为 {{ $statusLabel }}。房东确认后，平台会继续通过您留下的联系方式同步后续信息。
                </p>

                <dl class="mt-8 grid gap-4 text-sm">
                    <div class="flex items-center justify-between gap-4 border-b border-ink-100 pb-4">
                        <dt class="text-ink-500">订单编号</dt>
                        <dd class="font-semibold text-ink-900">#{{ $booking->id }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-ink-100 pb-4">
                        <dt class="text-ink-500">旅客</dt>
                        <dd class="font-semibold text-ink-900">{{ $booking->guest_name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-ink-100 pb-4">
                        <dt class="text-ink-500">入住</dt>
                        <dd class="font-semibold text-ink-900">{{ $booking->check_in->toDateString() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-ink-100 pb-4">
                        <dt class="text-ink-500">退房</dt>
                        <dd class="font-semibold text-ink-900">{{ $booking->check_out->toDateString() }}</dd>
                    </div>
                    @if ($booking->guest_adults !== null || $booking->guests)
                        <div class="flex items-center justify-between gap-4 border-b border-ink-100 pb-4">
                            <dt class="text-ink-500">人数</dt>
                            <dd class="font-semibold text-ink-900">{{ $booking->guestComposition()->label() }}</dd>
                        </div>
                    @endif
                    @if ($booking->notes)
                        <div class="grid gap-2 border-b border-ink-100 pb-4">
                            <dt class="text-ink-500">备注</dt>
                            <dd class="whitespace-pre-line font-medium text-ink-800">{{ $booking->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </article>

            <aside class="rounded-3xl bg-cream-100 p-6 md:p-8">
                <p class="text-sm font-semibold text-ink-500">预订住所</p>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-ink-900">{{ $booking->listing->title }}</h2>
                <p class="mt-3 text-sm leading-6 text-ink-600">
                    {{ $booking->listing->city }}{{ $booking->listing->address ? ' · '.$booking->listing->address : '' }}
                </p>
                <p class="mt-5 text-sm leading-6 text-ink-700">
                    {{ $booking->listing->description ?: '主人精心准备的城市住所，等待下一段旅程。' }}
                </p>
                <a href="{{ route('site.stays.show', $booking->listing) }}"
                   class="mt-6 inline-flex items-center justify-center rounded-2xl border border-ink-200 bg-white px-5 py-3 text-sm font-semibold text-ink-800 hover:bg-ink-100 transition">
                    查看房源
                </a>
            </aside>
        </div>
    </section>
</x-layouts.site>

<x-layouts.site :navActive="'bookings'">
    <x-slot:title>预订已提交 · Revebnb</x-slot:title>

    <section class="site-shell py-12 md:py-16">
        <div class="mx-auto max-w-3xl rounded-3xl bg-white p-8 md:p-10 ring-ink"
             data-booking-confirmation
             data-booking-id="{{ $booking->id }}"
             data-listing-title="{{ $booking->listing->title }}"
             data-check-in="{{ $booking->check_in->toDateString() }}"
             data-check-out="{{ $booking->check_out->toDateString() }}"
             data-detail-url="{{ $detailUrl }}">
            <p class="text-[11px] uppercase tracking-[0.4em] text-coral-600 font-semibold">Booking Submitted</p>
            <h1 class="mt-4 text-3xl md:text-4xl font-semibold text-ink-900 tracking-tight">预订已提交</h1>
            <p class="mt-3 text-sm leading-6 text-ink-600">
                我们已经收到您的预订请求。请保存此页面或下方链接，之后可以用它查看订单详情。
            </p>

            <div class="mt-8 grid gap-4 rounded-2xl bg-cream-100 p-5 text-sm text-ink-700">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-ink-500">住所</span>
                    <span class="text-right font-semibold text-ink-900">{{ $booking->listing->title }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-ink-500">日期</span>
                    <span class="text-right font-semibold text-ink-900">
                        {{ $booking->check_in->toDateString() }} 至 {{ $booking->check_out->toDateString() }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-ink-500">旅客</span>
                    <span class="text-right font-semibold text-ink-900">{{ $booking->guest_name }}</span>
                </div>
            </div>

            <div class="mt-8">
                <label for="booking-detail-url" class="block text-sm font-semibold text-ink-900">订单详情链接</label>
                <div class="mt-2 flex flex-col gap-3 md:flex-row">
                    <input id="booking-detail-url" type="text" readonly value="{{ $detailUrl }}"
                           class="flex-1 rounded-2xl border border-ink-200 bg-white px-4 py-3 text-sm text-ink-700 focus:outline-none">
                    <button type="button" data-copy-booking-url
                            class="coral-pill inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold transition">
                        复制链接
                    </button>
                </div>
                <p class="mt-3 text-xs leading-5 text-ink-500">
                    此链接包含您的访客访问令牌。为了保护隐私，请不要公开分享。
                </p>
            </div>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ $detailUrl }}"
                   class="inline-flex items-center justify-center rounded-2xl bg-ink-900 px-5 py-3 text-sm font-semibold text-white hover:bg-ink-700 transition">
                    查看订单详情
                </a>
                <a href="{{ route('site.me.bookings') }}"
                   class="inline-flex items-center justify-center rounded-2xl border border-ink-200 px-5 py-3 text-sm font-semibold text-ink-800 hover:bg-ink-100 transition">
                    我的订单
                </a>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            (() => {
                const confirmation = document.querySelector('[data-booking-confirmation]');

                if (! confirmation) {
                    return;
                }

                const detailUrl = confirmation.dataset.detailUrl || '';
                const row = {
                    booking_id: Number(confirmation.dataset.bookingId),
                    listing_title: confirmation.dataset.listingTitle || '',
                    check_in: confirmation.dataset.checkIn || '',
                    check_out: confirmation.dataset.checkOut || '',
                    detail_url: detailUrl,
                };
                const key = 'revebnb:guestBookings';

                try {
                    const existing = JSON.parse(window.localStorage.getItem(key) || '[]');
                    const rows = Array.isArray(existing) ? existing.filter((item) => item.booking_id !== row.booking_id) : [];
                    rows.unshift(row);
                    window.localStorage.setItem(key, JSON.stringify(rows.slice(0, 20)));
                } catch (error) {
                    window.localStorage.setItem(key, JSON.stringify([row]));
                }

                document.querySelector('[data-copy-booking-url]')?.addEventListener('click', async () => {
                    await window.navigator.clipboard?.writeText(detailUrl);
                });
            })();
        </script>
    @endpush
</x-layouts.site>

<x-layouts.site :navActive="'bookings'">
    <x-slot:title>我的订单 · Revebnb</x-slot:title>

    <section class="site-shell py-12 md:py-16"
             x-data="{
                 bookings: [],
                 init() {
                     try {
                         const stored = JSON.parse(window.localStorage.getItem('revebnb:guestBookings') || '[]');
                         this.bookings = Array.isArray(stored) ? stored : [];
                     } catch (error) {
                         this.bookings = [];
                     }
                 },
             }">
        <div class="max-w-4xl">
            <p class="text-[11px] uppercase tracking-[0.4em] text-coral-600 font-semibold">My Bookings</p>
            <h1 class="mt-4 text-3xl md:text-5xl font-semibold text-ink-900 tracking-tight">我的订单</h1>
            <p class="mt-3 text-sm leading-6 text-ink-600">
                这里会显示您在本设备提交过的 Revebnb 预订。订单链接仅保存在当前浏览器中。
            </p>
        </div>

        <div class="mt-10 rounded-3xl bg-white p-6 md:p-8 ring-ink">
            <div x-show="bookings.length === 0" x-cloak class="rounded-2xl border border-dashed border-ink-200 p-10 text-center">
                <p class="text-lg font-semibold text-ink-900">暂无订单</p>
                <p class="mt-2 text-sm text-ink-500">提交预订后，确认页会自动把订单保存到这里。</p>
                <a href="{{ route('site.stays.index') }}"
                   class="mt-6 inline-flex items-center justify-center rounded-2xl bg-ink-900 px-5 py-3 text-sm font-semibold text-white hover:bg-ink-700 transition">
                    浏览住宿
                </a>
            </div>

            <div x-show="bookings.length > 0" x-cloak class="grid gap-4">
                <template x-for="booking in bookings" :key="booking.booking_id">
                    <a :href="booking.detail_url"
                       class="group rounded-2xl border border-ink-100 p-5 hover:border-ink-300 hover:bg-cream-100 transition">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-ink-500" x-text="`订单 #${booking.booking_id}`"></p>
                                <h2 class="mt-1 text-lg font-semibold text-ink-900" x-text="booking.listing_title"></h2>
                                <p class="mt-1 text-sm text-ink-600" x-text="`${booking.check_in} 至 ${booking.check_out}`"></p>
                            </div>
                            <span class="text-sm font-semibold text-coral-600 group-hover:text-coral-700">查看详情</span>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </section>
</x-layouts.site>

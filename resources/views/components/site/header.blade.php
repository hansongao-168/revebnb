@props([
    'active' => 'stays',
])

<header class="sticky top-0 z-40 border-b border-ink-100 bg-cream-50/85 backdrop-blur-md">
    <div class="site-shell flex items-center justify-between h-20">
        <a href="{{ route('site.stays.index') }}" class="flex items-center gap-2 group">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full coral-pill">
                <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-white" aria-hidden="true">
                    <path d="M12 2.5 3 11.5h2v8.5h5v-5h4v5h5V11.5h2L12 2.5Z"/>
                </svg>
            </span>
            <span class="flex flex-col leading-none">
                <span class="text-[1.35rem] font-semibold tracking-tight text-ink-900">revebnb</span>
                <span class="text-[10px] uppercase tracking-[0.32em] text-ink-400 -mt-0.5">city stays</span>
            </span>
        </a>

        <nav class="hidden md:flex items-center gap-10 text-[0.95rem] font-medium text-ink-700">
            <a href="{{ route('site.stays.index') }}"
               class="nav-link"
               data-active="{{ $active === 'stays' ? 'true' : 'false' }}">住宿</a>
            <a href="{{ route('site.me.bookings') }}"
               class="nav-link"
               data-active="{{ $active === 'bookings' ? 'true' : 'false' }}">我的订单</a>
            <a href="{{ route('site.stays.index', ['kind' => 'experiences']) }}" class="nav-link text-ink-400">体验</a>
            <a href="{{ route('site.stays.index', ['kind' => 'long-stay']) }}" class="nav-link text-ink-400">长租</a>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ url('/landlord-portal/login') }}"
               class="hidden sm:inline-flex items-center text-sm font-medium text-ink-700 px-4 py-2 rounded-full hover:bg-ink-100 transition">
                成为房东
            </a>

            <button type="button"
                    class="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white py-1.5 pl-3 pr-1.5 text-ink-700 hover:shadow-md transition">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" class="h-3.5 w-3.5">
                    <path d="M2.5 4.5h11M2.5 8h11M2.5 11.5h11" stroke-linecap="round"/>
                </svg>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-ink-700 text-white text-xs font-semibold">
                    @auth
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    @else
                        Re
                    @endauth
                </span>
            </button>
        </div>
    </div>
</header>

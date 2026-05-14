<footer class="mt-24 border-t border-ink-100 bg-cream-100/60">
    <div class="site-shell py-14 grid grid-cols-2 md:grid-cols-4 gap-10 text-sm text-ink-600">
        <div class="col-span-2 md:col-span-1">
            <p class="text-[11px] uppercase tracking-[0.32em] text-ink-400 mb-3">关于 revebnb</p>
            <p class="leading-relaxed text-ink-700">
                我们用编辑级品味精选每一处住所，让一次城市停留成为可被记忆的章节。
            </p>
        </div>

        <div>
            <p class="text-[11px] uppercase tracking-[0.32em] text-ink-400 mb-3">探索</p>
            <ul class="space-y-2">
                <li><a class="hover:text-ink-900 transition" href="{{ route('site.stays.index') }}">所有住宿</a></li>
                <li><a class="hover:text-ink-900 transition" href="#">编辑精选</a></li>
                <li><a class="hover:text-ink-900 transition" href="#">城市指南</a></li>
                <li><a class="hover:text-ink-900 transition" href="#">长期旅居</a></li>
            </ul>
        </div>

        <div>
            <p class="text-[11px] uppercase tracking-[0.32em] text-ink-400 mb-3">房东</p>
            <ul class="space-y-2">
                <li><a class="hover:text-ink-900 transition" href="{{ url('/landlord-portal/login') }}">房东登录</a></li>
                <li><a class="hover:text-ink-900 transition" href="#">挂牌房源</a></li>
                <li><a class="hover:text-ink-900 transition" href="#">收益预估</a></li>
            </ul>
        </div>

        <div>
            <p class="text-[11px] uppercase tracking-[0.32em] text-ink-400 mb-3">支持</p>
            <ul class="space-y-2">
                <li><a class="hover:text-ink-900 transition" href="#">帮助中心</a></li>
                <li><a class="hover:text-ink-900 transition" href="#">退订政策</a></li>
                <li><a class="hover:text-ink-900 transition" href="#">隐私与条款</a></li>
            </ul>
        </div>
    </div>

    <div class="site-shell border-t border-ink-100 py-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs text-ink-500">
        <p>© {{ date('Y') }} Revebnb · All rights reserved.</p>
        <div class="flex items-center gap-4">
            <span>简体中文 (中国)</span>
            <span>·</span>
            <span>CNY ¥</span>
        </div>
    </div>
</footer>

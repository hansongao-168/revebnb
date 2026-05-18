<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>外部日历（ICS）同步 — 运营说明</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "PingFang SC", "Microsoft YaHei", sans-serif;
            line-height: 1.6;
            max-width: 52rem;
            margin: 0 auto;
            padding: 1.5rem 1.25rem 3rem;
        }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        h2 { font-size: 1.1rem; margin-top: 1.75rem; }
        h3 { font-size: 1rem; margin-top: 1.25rem; }
        ul { padding-left: 1.25rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; margin: 0.75rem 0; }
        th, td { border: 1px solid color-mix(in srgb, CanvasText 15%, transparent); padding: 0.4rem 0.6rem; text-align: left; }
        th { background: color-mix(in srgb, CanvasText 6%, transparent); }
        code, .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.88em;
            word-break: break-all;
        }
        .note {
            border: 1px solid color-mix(in srgb, CanvasText 18%, transparent);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin: 1rem 0;
            background: color-mix(in srgb, CanvasText 4%, transparent);
        }
        .warn { border-color: #d97706; }
        @media print {
            body { max-width: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <p class="no-print note">
        仓库内 Markdown 副本：<span class="mono">docs/operations/ics-external-calendar.md</span>。
        使用浏览器「打印」可另存为 PDF 分发给运营。
    </p>

    <h1>外部日历（ICS）同步 — 运营说明</h1>
    <p>把 Airbnb 等平台的 iCal 链接绑定到房源，拉取外部占用日期，在后台对比展示；可选合并进访客站 <span class="mono">/stays</span> 可订日历。<strong>单向读取，不会写回 Airbnb。</strong></p>

    <h2>谁能做什么</h2>
    <table>
        <thead>
            <tr>
                <th>角色</th>
                <th>面板</th>
                <th>配置 / 同步 ICS</th>
                <th>日历对比</th>
                <th>挡前台预订</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>平台运营</td>
                <td><span class="mono">/admin</span></td>
                <td>✅</td>
                <td>✅</td>
                <td>✅ 按 feed 开关</td>
            </tr>
            <tr>
                <td>租户</td>
                <td><span class="mono">/tenant-admin</span></td>
                <td>—</td>
                <td>只读</td>
                <td>—</td>
            </tr>
            <tr>
                <td>房东</td>
                <td><span class="mono">/landlord-portal</span></td>
                <td>—</td>
                <td>只读</td>
                <td>—</td>
            </tr>
        </tbody>
    </table>

    <h2 id="airbnb-ical">获取 Airbnb ICS 链接</h2>
    <ol>
        <li>Airbnb 房东后台 → 房源 → <strong>日历</strong> → 导出 / 同步日历。</li>
        <li>复制 iCal 链接，形如：<br>
            <code>https://www.airbnb.fr/calendar/ical/{id}.ics?t={token}</code>
        </li>
        <li>仅粘贴到平台 <span class="mono">/admin</span>，勿公开传播（含私密 token）。</li>
    </ol>

    <h2>平台后台操作</h2>
    <h3>绑定订阅</h3>
    <ol>
        <li><span class="mono">/admin</span> → <strong>租房</strong> → <strong>房源</strong> → 编辑目标房源。</li>
        <li>下方 <strong>外部日历</strong> → 新建。</li>
        <li>填写显示名称、ICS URL、启用同步；间隔留空则默认 <strong>6 小时</strong>。</li>
        <li><strong>合并进前台可订</strong> 建议先关闭，对比无误后再开。</li>
        <li>保存后点 <strong>立即同步</strong>，或等待每小时定时同步。</li>
    </ol>

    <h3>日历对比</h3>
    <p>编辑页顶栏 <strong>日历对比</strong>，或 <span class="mono">/admin/listings/{id}/calendar</span>。可查看外部 / 本地 / 重叠占用与当月事件列表。</p>

    <h2>合并进前台可订</h2>
    <p class="note warn">开启后，该 feed 的外部占用日会在 <span class="mono">/stays</span> 显示为不可订并拒绝下单。务必先在对比页核对。</p>

    <h2>运维检查清单</h2>
    <ul>
        <li>部署后：<span class="mono">php artisan migrate</span></li>
        <li>Cron 每分钟：<span class="mono">php artisan schedule:run</span>（已注册每小时 <span class="mono">calendar-feeds:sync-due</span>）</li>
        <li>生产运行队列：<span class="mono">php artisan queue:work</span></li>
    </ul>

    <h2>常见问题</h2>
    <ul>
        <li><strong>同步失败</strong>：Airbnb token 失效 → 在 Airbnb 重新导出链接，后台更新 feed URL。</li>
        <li><strong>前台仍能选</strong>：未开启「合并进前台可订」。</li>
        <li><strong>对比无数据</strong>：未同步成功、月份不对、或绑错房源链接。</li>
    </ul>

    <h2>路径速查</h2>
    <ul>
        <li>平台后台：<span class="mono">/admin</span></li>
        <li>日历对比：<span class="mono">/admin/listings/{id}/calendar</span></li>
        <li>租户对比：<span class="mono">/tenant-admin/listings/{id}/calendar</span></li>
        <li>房东对比：<span class="mono">/landlord-portal/listings/{id}/calendar</span></li>
        <li>访客详情：<span class="mono">/stays/{slug}</span></li>
    </ul>

    <p class="note"><small>文档版本：2026-05-18</small></p>
</body>
</html>

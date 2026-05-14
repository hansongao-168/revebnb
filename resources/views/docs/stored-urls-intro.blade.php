<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>URL 入库功能说明</title>
    <style>
        :root {
            color-scheme: light dark;
        }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "PingFang SC", "Microsoft YaHei", sans-serif;
            line-height: 1.6;
            max-width: 48rem;
            margin: 0 auto;
            padding: 1.5rem 1.25rem 3rem;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        h2 {
            font-size: 1.1rem;
            margin-top: 1.75rem;
        }
        ul {
            padding-left: 1.25rem;
        }
        code, .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.9em;
        }
        .note {
            border: 1px solid color-mix(in srgb, CanvasText 18%, transparent);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin: 1rem 0;
            background: color-mix(in srgb, CanvasText 4%, transparent);
        }
        @media print {
            body { max-width: none; padding: 0; }
            .no-print { display: none !important; }
            a[href]::after { content: ""; }
        }
    </style>
</head>
<body>
    <p class="no-print note">
        需要离线 PDF 时：使用浏览器「打印」→「另存为 PDF」，或运行
        <span class="mono">php artisan docs:build-stored-url-intro-pdf</span>
        （需已安装 <span class="mono">weasyprint</span>）生成
        <span class="mono">public/docs/stored-urls-intro.pdf</span>。
    </p>

    <h1>URL 入库功能说明</h1>
    <p>在平台管理后台集中保存常用链接（文档、监控、第三方工具等），便于团队检索与复用。</p>

    <h2>使用入口</h2>
    <ul>
        <li>登录管理后台后，侧栏分组「工具」→「URL 书签」。</li>
        <li>列表页右上角可打开本说明网页或已生成的 PDF。</li>
    </ul>

    <h2>字段说明</h2>
    <ul>
        <li><strong>标题</strong>：简短名称，便于在列表中识别。</li>
        <li><strong>URL</strong>：完整地址（含 <span class="mono">https://</span>），系统会校验 URL 格式。</li>
        <li><strong>说明</strong>：可选，补充用途、账号范围或注意事项。</li>
    </ul>

    <h2>常见操作</h2>
    <ul>
        <li><strong>新增</strong>：列表页「新建」填写表单后保存。</li>
        <li><strong>复制链接</strong>：在列表「URL」列点击即可复制到剪贴板（HTTPS 环境下）。</li>
        <li><strong>编辑 / 删除</strong>：使用行内「编辑」或批量删除。</li>
    </ul>

    <h2>权限</h2>
    <p>仅具备平台管理员后台访问权限的账号可使用本功能。</p>
</body>
</html>

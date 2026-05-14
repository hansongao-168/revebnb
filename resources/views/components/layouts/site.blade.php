<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Revebnb') }}</title>
    <meta name="description" content="{{ $description ?? '在 Revebnb 寻找城市里一处安心的住所。精选民宿、长短租公寓、城市旅居首选。' }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-body min-h-screen flex flex-col">
    <x-site.header :active="$navActive ?? 'stays'" />

    <main class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>

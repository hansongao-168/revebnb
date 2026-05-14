<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>您的 Revebnb 预订已提交</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1f2933; line-height: 1.6;">
    <p>您好，{{ $booking->guest_name ?? '旅客' }}：</p>

    <p>您的 Revebnb 预订已提交。您可以通过下方链接查看订单详情和后续状态：</p>

    <p>
        <a href="{{ $detailUrl }}" style="color: #ff385c;">查看我的预订</a>
    </p>

    <p>
        为保护您的隐私，此链接将在 {{ config('guest_booking.token_ttl_days') }} 天后失效。请勿将链接转发给他人。
    </p>

    <p>谢谢，<br>{{ config('app.name') }}</p>
</body>
</html>

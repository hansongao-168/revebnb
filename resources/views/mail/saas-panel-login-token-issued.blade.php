<x-mail::message>
# 您好，{{ $saasUser->name }}

请点击下方按钮进入租户管理后台（请勿转发他人）。

<x-mail::button :url="$url">
进入租户后台
</x-mail::button>

@if($expiresAtDisplay)
链接有效期至：**{{ $expiresAtDisplay }}**（{{ config('app.timezone') }}）
@endif

<x-mail::panel>
若您未申请此链接，请忽略本邮件。怀疑泄露请联系平台管理员重新签发入口链接。
</x-mail::panel>

谢谢，  
{{ config('app.name') }}
</x-mail::message>

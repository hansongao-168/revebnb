<x-mail::message>
# 您好，{{ $landlord->name }}

请点击下方按钮进入房东控制台（请勿转发他人）。

<x-mail::button :url="$loginUrl">
进入控制台
</x-mail::button>

链接有效期至：**{{ $expiresAtDisplay }}**（{{ config('app.timezone') }}）

<x-mail::panel>
若您未申请此链接，请忽略本邮件。怀疑泄露请联系平台管理员重新发送入口链接。
</x-mail::panel>

谢谢，  
{{ config('app.name') }}
</x-mail::message>

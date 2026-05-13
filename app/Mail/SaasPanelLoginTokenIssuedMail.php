<?php

namespace App\Mail;

use App\Models\SaasUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SaasPanelLoginTokenIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public SaasUser $saasUser,
        public string $url,
        public string $context,
        public ?string $expiresAtDisplay = null,
    ) {}

    public function build(): self
    {
        return $this->subject('租户后台入口链接')
            ->markdown('mail.saas-panel-login-token-issued');
    }
}

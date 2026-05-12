<?php

namespace App\Mail;

use App\Models\Landlord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LandlordPortalAccessMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Landlord $landlord,
        public string $loginUrl,
        public string $expiresAtDisplay,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '房东控制台入口链接',
            to: [
                new Address($this->landlord->email, $this->landlord->name),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.landlord-portal-access',
        );
    }
}

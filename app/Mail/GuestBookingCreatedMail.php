<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestBookingCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $detailUrl;

    public function __construct(
        public Booking $booking,
        public string $plainToken,
    ) {
        $this->detailUrl = route('site.bookings.show', [
            'booking' => $this->booking,
            'token' => $this->plainToken,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '您的 Revebnb 预订已提交',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.guest-booking-created',
        );
    }
}

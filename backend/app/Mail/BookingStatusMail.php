<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public string $action
    ) {}

    public function envelope(): Envelope
    {
        $code = $this->reservation->reservation_code;

        $subject = match ($this->action) {
            'received' => "Booking Diterima - {$code}",
            'confirmed' => "Booking Dikonfirmasi - {$code}",
            'rejected' => "Booking Ditolak - {$code}",
            'cancelled' => "Booking Dibatalkan - {$code}",
            default => "Update Reservasi - {$code}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-status',
            with: [
                'reservation' => $this->reservation,
                'action' => $this->action,
            ]
        );
    }
}

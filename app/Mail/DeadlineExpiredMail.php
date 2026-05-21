<?php

namespace App\Mail;

use App\Models\LegalAct;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeadlineExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LegalAct $legalAct) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tapşırığın İcra Müddəti Başa Çatdı',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deadline-expired',
        );
    }
}

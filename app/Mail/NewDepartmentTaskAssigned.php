<?php

namespace App\Mail;

use App\Models\LegalAct;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDepartmentTaskAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LegalAct $legalAct) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yeni Tapşırıq Təyin Edildi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-department-task-assigned',
        );
    }
}

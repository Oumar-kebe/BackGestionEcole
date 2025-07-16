<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecapitulatifHebdomadaire extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Récapitulatif hebdomadaire - Semaine du ' . $this->data['semaine']['debut'],
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.recapitulatif-hebdomadaire',
            with: $this->data
        );
    }

    public function attachments()
    {
        return [];
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouvelleNote extends Mailable
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
            subject: 'Nouvelle note en ' . $this->data['matiere']->nom,
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.nouvelle-note',
            with: $this->data
        );
    }

    public function attachments()
    {
        return [];
    }
}

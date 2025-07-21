<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenueMail extends Mailable
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
            subject: 'Bienvenue sur GestionEcole - Vos identifiants de connexion',
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.bienvenue',
            with: [
                'user' => $this->data['user'],
                'motDePasse' => $this->data['motDePasse'],
                'loginUrl' => $this->data['loginUrl'],
            ]
        );
    }

    public function attachments()
    {
        return [];
    }
}

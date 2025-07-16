<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Bulletin;

class BulletinDisponible extends Mailable
{
    use Queueable, SerializesModels;

    public $bulletin;

    public function __construct(Bulletin $bulletin)
    {
        $this->bulletin = $bulletin;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Nouveau bulletin disponible - ' . $this->bulletin->periode->nom,
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.bulletin-disponible',
            with: [
                'eleve' => $this->bulletin->eleve,
                'periode' => $this->bulletin->periode,
                'classe' => $this->bulletin->classe,
                'moyenneGenerale' => $this->bulletin->moyenne_generale,
                'mention' => $this->bulletin->mention_label,
                'rang' => $this->bulletin->rang . '/' . $this->bulletin->effectif_classe,
            ]
        );
    }

    public function attachments()
    {
        return [];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bulletin extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'periode_id',
        'moyenne_generale',
        'rang',
        'effectif_classe',
        'mention',
        'observation_conseil',
        'fichier_pdf',
        'genere_le'
    ];

    protected $casts = [
        'moyenne_generale' => 'decimal:2',
        'rang' => 'integer',
        'effectif_classe' => 'integer',
        'genere_le' => 'datetime'
    ];

    // Relations
    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }

    // Helpers
    public function genererMention()
    {
        if ($this->moyenne_generale === null) return null;

        $this->mention = match(true) {
            $this->moyenne_generale >= 18 => 'excellent',
            $this->moyenne_generale >= 16 => 'tres_bien',
            $this->moyenne_generale >= 14 => 'bien',
            $this->moyenne_generale >= 12 => 'assez_bien',
            $this->moyenne_generale >= 10 => 'passable',
            default => 'insuffisant'
        };
    }

    public function getMentionLabelAttribute()
    {
        return match($this->mention) {
            'excellent' => 'Excellent',
            'tres_bien' => 'Très bien',
            'bien' => 'Bien',
            'assez_bien' => 'Assez bien',
            'passable' => 'Passable',
            'insuffisant' => 'Insuffisant',
            default => ''
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleve_id',
        'matiere_id',
        'periode_id',
        'enseignant_id',
        'note_devoir1',
        'note_devoir2',
        'note_composition',
        'moyenne',
        'appreciation'
    ];

    protected $casts = [
        'note_devoir1' => 'decimal:2',
        'note_devoir2' => 'decimal:2',
        'note_composition' => 'decimal:2',
        'moyenne' => 'decimal:2'
    ];

    // Relations
    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($note) {
            $note->calculerMoyenne();
            $note->genererAppreciation();
        });
    }

    // Helpers
    public function calculerMoyenne()
    {
        $notes = array_filter([
            $this->note_devoir1,
            $this->note_devoir2,
            $this->note_composition * 2 // La composition compte double
        ], function($n) { return $n !== null; });

        if (count($notes) > 0) {
            $this->moyenne = array_sum($notes) / (count($notes) > 2 ? 4 : count($notes));
        }
    }

    public function genererAppreciation()
    {
        if ($this->moyenne === null) return;

        $this->appreciation = match(true) {
            $this->moyenne >= 18 => 'Excellent',
            $this->moyenne >= 16 => 'Très bien',
            $this->moyenne >= 14 => 'Bien',
            $this->moyenne >= 12 => 'Assez bien',
            $this->moyenne >= 10 => 'Passable',
            default => 'Insuffisant'
        };
    }
}

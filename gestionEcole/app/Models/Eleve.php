<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eleve extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nationalite',
        'groupe_sanguin',
        'allergies',
        'maladies',
        'personne_urgence_nom',
        'personne_urgence_telephone'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function parents()
    {
        return $this->belongsToMany(ParentEleve::class, 'parents_eleves', 'eleve_id', 'parent_id')
            ->withPivot('lien_parente')
            ->withTimestamps();
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function bulletins()
    {
        return $this->hasMany(Bulletin::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // Helpers
    public function getInscriptionActuelleAttribute()
    {
        $anneeCourante = AnneeScolaire::anneeCourante();
        return $this->inscriptions()
            ->where('annee_scolaire_id', $anneeCourante?->id)
            ->where('statut', 'en_cours')
            ->first();
    }

    public function getClasseActuelleAttribute()
    {
        return $this->inscriptionActuelle?->classe;
    }

    public function estInscrit()
    {
        return $this->inscriptionActuelle !== null;
    }
}

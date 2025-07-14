<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'niveau_id',
        'annee_scolaire_id',
        'capacite',
        'actif'
    ];

    protected $casts = [
        'actif' => 'boolean'
    ];

    // Relations
    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'enseignants_classes')
            ->withPivot('matiere_id')
            ->withTimestamps();
    }

    public function bulletins()
    {
        return $this->hasMany(Bulletin::class);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeAnneeCourante($query)
    {
        $anneeCourante = AnneeScolaire::anneeCourante();
        return $query->where('annee_scolaire_id', $anneeCourante?->id);
    }

    // Helpers
    public function getEffectifAttribute()
    {
        return $this->inscriptions()->where('statut', 'en_cours')->count();
    }

    public function getPlacesDisponiblesAttribute()
    {
        return $this->capacite - $this->effectif;
    }

    public function estPleine()
    {
        return $this->effectif >= $this->capacite;
    }
}

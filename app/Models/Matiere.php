<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
        'coefficient',
        'niveau_id',
        'actif'
    ];

    protected $casts = [
        'coefficient' => 'decimal:1',
        'actif' => 'boolean'
    ];

    // Relations
    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'enseignants_matieres')
            ->withPivot('annee_scolaire_id')
            ->withTimestamps();
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeParNiveau($query, $niveauId)
    {
        return $query->where('niveau_id', $niveauId);
    }
}

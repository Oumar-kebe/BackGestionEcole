<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enseignant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialite',
        'diplome',
        'annees_experience'
    ];

    protected $casts = [
        'annees_experience' => 'integer'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function matieres()
    {
        return $this->belongsToMany(Matiere::class, 'enseignants_matieres')
            ->withPivot('annee_scolaire_id')
            ->withTimestamps();
    }

    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'enseignants_classes')
            ->withPivot('matiere_id')
            ->withTimestamps();
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    // Helpers
    public function enseigneMatiere($matiereId, $anneeId = null)
    {
        $anneeId = $anneeId ?? AnneeScolaire::anneeCourante()?->id;
        return $this->matieres()
            ->wherePivot('annee_scolaire_id', $anneeId)
            ->where('matieres.id', $matiereId)
            ->exists();
    }

    public function enseigneClasse($classeId, $matiereId)
    {
        return $this->classes()
            ->wherePivot('matiere_id', $matiereId)
            ->where('classes.id', $classeId)
            ->exists();
    }

    public function getClassesParMatiereAttribute()
    {
        $result = [];
        $relations = $this->classes()->withPivot('matiere_id')->get();

        foreach ($relations as $classe) {
            $matiereId = $classe->pivot->matiere_id;
            if (!isset($result[$matiereId])) {
                $result[$matiereId] = [];
            }
            $result[$matiereId][] = $classe;
        }

        return $result;
    }
}

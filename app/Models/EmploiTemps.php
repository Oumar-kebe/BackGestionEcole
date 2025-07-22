<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmploiTemps extends Model
{
    use HasFactory;

    protected $table = 'emploi_temps';

    protected $fillable = [
        'jour',
        'heure_debut',
        'heure_fin',
        'matiere',
        'professeur',
        'salle',
        'classe',
        'niveau',
        'description',
        'statut'
    ];

    protected $casts = [
        'heure_debut' => 'datetime:H:i',
        'heure_fin' => 'datetime:H:i',
    ];

    // Scopes pour faciliter les requêtes
    public function scopeByJour($query, $jour)
    {
        return $query->where('jour', $jour);
    }

    public function scopeByClasse($query, $classe)
    {
        return $query->where('classe', $classe);
    }

    public function scopeByProfesseur($query, $professeur)
    {
        return $query->where('professeur', $professeur);
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeOrdonneParHeure($query)
    {
        return $query->orderBy('heure_debut');
    }

    // Mutateurs pour formater les heures
    public function getHeureDebutFormateeAttribute()
    {
        return Carbon::parse($this->heure_debut)->format('H:i');
    }

    public function getHeureFinFormateeAttribute()
    {
        return Carbon::parse($this->heure_fin)->format('H:i');
    }

    // Accesseur pour obtenir la durée du cours
    public function getDureeAttribute()
    {
        $debut = Carbon::parse($this->heure_debut);
        $fin = Carbon::parse($this->heure_fin);
        return $debut->diff($fin)->format('%H:%I');
    }

    // Méthode pour vérifier les conflits d'horaires
    public static function verifierConflitHoraire($jour, $heure_debut, $heure_fin, $salle = null, $professeur = null, $excludeId = null)
    {
        $query = self::where('jour', $jour)
            ->where('statut', 'actif')
            ->where(function ($q) use ($heure_debut, $heure_fin) {
                $q->whereBetween('heure_debut', [$heure_debut, $heure_fin])
                  ->orWhereBetween('heure_fin', [$heure_debut, $heure_fin])
                  ->orWhere(function ($subQ) use ($heure_debut, $heure_fin) {
                      $subQ->where('heure_debut', '<=', $heure_debut)
                           ->where('heure_fin', '>=', $heure_fin);
                  });
            });

        if ($salle) {
            $query->where('salle', $salle);
        }

        if ($professeur) {
            $query->orWhere('professeur', $professeur);
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}

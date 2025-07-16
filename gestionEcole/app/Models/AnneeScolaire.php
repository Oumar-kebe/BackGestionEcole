<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnneeScolaire extends Model
{
    use HasFactory;
    protected $table = 'annees_scolaires';

    protected $fillable = [
        'libelle',
        'date_debut',
        'date_fin',
        'actuelle'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actuelle' => 'boolean'
    ];

    // Relations
    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function periodes()
    {
        return $this->hasMany(Periode::class);
    }

    public function enseignantsMatieres()
    {
        return $this->hasMany(EnseignantMatiere::class);
    }

    // Scopes
    public function scopeActuelle($query)
    {
        return $query->where('actuelle', true);
    }

    // Helpers
    public static function anneeCourante()
    {
        return self::where('actuelle', true)->first();
    }

    public function setActuelleAttribute($value)
    {
        if ($value) {
            self::where('actuelle', true)->update(['actuelle' => false]);
        }
        $this->attributes['actuelle'] = $value;
    }
}

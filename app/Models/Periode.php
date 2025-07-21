<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'type',
        'ordre',
        'annee_scolaire_id',
        'date_debut',
        'date_fin',
        'actuelle'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actuelle' => 'boolean',
        'ordre' => 'integer'
    ];

    // Relations
    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function bulletins()
    {
        return $this->hasMany(Bulletin::class);
    }

    // Scopes
    public function scopeActuelle($query)
    {
        return $query->where('actuelle', true);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helpers
    public static function periodeActuelle()
    {
        return self::where('actuelle', true)->first();
    }

    public function setActuelleAttribute($value)
    {
        if ($value) {
            self::where('annee_scolaire_id', $this->annee_scolaire_id)
                ->where('actuelle', true)
                ->update(['actuelle' => false]);
        }
        $this->attributes['actuelle'] = $value;
    }
}

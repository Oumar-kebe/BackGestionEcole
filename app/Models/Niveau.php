<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Niveau extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
        'ordre'
    ];

    // Relations
    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function matieres()
    {
        return $this->hasMany(Matiere::class);
    }

    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentEleve extends Model
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'profession',
        'lieu_travail',
        'telephone_bureau'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enfants()
    {
        return $this->belongsToMany(Eleve::class, 'parents_eleves', 'parent_id', 'eleve_id')
            ->withPivot('lien_parente')
            ->withTimestamps();
    }

    // Helpers
    public function peutVoirEleve($eleveId)
    {
        return $this->enfants()->where('eleves.id', $eleveId)->exists();
    }

    public function bulletinsEnfants()
{
    return Bulletin::whereIn('eleve_id', $this->enfants->pluck('id'))->get();
}
}

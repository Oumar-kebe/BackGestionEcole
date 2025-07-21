<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nom',
        'prenom',
        'role',
        'telephone',
        'adresse',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'matricule',
        'actif'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_naissance' => 'date',
        'actif' => 'boolean',
    ];

    // Guard pour Spatie Permission
    protected $guard_name = 'api';

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'matricule' => $this->matricule,
        ];
    }

    // Relations
    public function eleve()
    {
        return $this->hasOne(Eleve::class);
    }

    public function enseignant()
    {
        return $this->hasOne(Enseignant::class);
    }

    public function parent()
    {
        return $this->hasOne(ParentEleve::class);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // Helpers
    public function isAdmin()
    {
        return $this->role === 'administrateur';
    }

    public function isEnseignant()
    {
        return $this->role === 'enseignant';
    }

    public function isEleve()
    {
        return $this->role === 'eleve';
    }

    public function isParent()
    {
        return $this->role === 'parent';
    }

    public function getNomCompletAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }
}

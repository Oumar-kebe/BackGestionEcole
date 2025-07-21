<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class MatriculeService
{
    public function generer($role, $anneeScolaire = null)
    {
        $prefix = $this->getPrefixRole($role);
        $year = $anneeScolaire ? substr($anneeScolaire, 0, 4) : date('Y');

        $lastMatricule = User::where('matricule', 'like', $prefix . $year . '%')
            ->where('role', $role)
            ->orderBy('matricule', 'desc')
            ->first();

        if ($lastMatricule) {
            $lastNumber = intval(substr($lastMatricule->matricule, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function genererTemporaire()
    {
        return 'TMP' . date('YmdHis') . Str::random(4);
    }

    public function regenerer(User $user)
    {
        $nouveauMatricule = $this->generer($user->role);
        $user->update(['matricule' => $nouveauMatricule]);

        return $nouveauMatricule;
    }

    public function verifierUnicite($matricule)
    {
        return !User::where('matricule', $matricule)->exists();
    }

    private function getPrefixRole($role)
    {
        return match($role) {
            'administrateur' => 'ADM',
            'enseignant' => 'ENS',
            'eleve' => 'ELV',
            'parent' => 'PAR',
            default => 'USR'
        };
    }

    public function extraireInfos($matricule)
    {
        if (strlen($matricule) < 11) {
            return null;
        }

        $prefix = substr($matricule, 0, 3);
        $year = substr($matricule, 3, 4);
        $number = substr($matricule, 7, 4);

        $role = match($prefix) {
            'ADM' => 'administrateur',
            'ENS' => 'enseignant',
            'ELV' => 'eleve',
            'PAR' => 'parent',
            'USR' => 'utilisateur',
            default => null
        };

        return [
            'role' => $role,
            'annee' => $year,
            'numero' => intval($number),
            'prefix' => $prefix
        ];
    }

    public function genererLot($role, $nombre, $anneeScolaire = null)
    {
        $matricules = [];

        for ($i = 0; $i < $nombre; $i++) {
            $matricules[] = $this->generer($role, $anneeScolaire);
        }

        return $matricules;
    }
}

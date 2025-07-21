<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'nullable|in:administrateur,enseignant,eleve,parent',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:M,F',

            // Champs spécifiques élève
            'nationalite' => 'nullable|string|required_if:role,eleve',
            'groupe_sanguin' => 'nullable|string|max:5',
            'allergies' => 'nullable|string',
            'maladies' => 'nullable|string',
            'personne_urgence_nom' => 'nullable|string|max:255',
            'personne_urgence_telephone' => 'nullable|string|max:20',

            // Champs spécifiques enseignant
            'specialite' => 'nullable|string|required_if:role,enseignant',
            'diplome' => 'nullable|string',
            'annees_experience' => 'nullable|integer|min:0',

            // Champs spécifiques parent
            'profession' => 'nullable|string|required_if:role,parent',
            'lieu_travail' => 'nullable|string',
            'telephone_bureau' => 'nullable|string|max:20',
        ];
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le nom est obligatoire',
            'prenom.required' => 'Le prénom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
            'role.in' => 'Le rôle doit être administrateur, enseignant, eleve ou parent',
            'sexe.in' => 'Le sexe doit être M ou F',
            'date_naissance.before' => 'La date de naissance doit être dans le passé',
            'nationalite.required_if' => 'La nationalité est obligatoire pour un élève',
            'specialite.required_if' => 'La spécialité est obligatoire pour un enseignant',
            'profession.required_if' => 'La profession est obligatoire pour un parent',
        ];
    }
}

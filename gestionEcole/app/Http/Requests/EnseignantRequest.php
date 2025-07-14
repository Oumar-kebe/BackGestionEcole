<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnseignantRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user() && auth()->user()->role === 'administrateur';
    }

    public function rules()
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'telephone' => 'required|string|max:20',
            'adresse' => 'required|string',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'required|string|max:255',
            'sexe' => 'required|in:M,F',
            'specialite' => 'required|string|max:255',
            'diplome' => 'required|string|max:255',
            'annees_experience' => 'nullable|integer|min:0|max:50',
            'matieres' => 'required|array|min:1',
            'matieres.*' => 'exists:matieres,id',
        ];
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le nom est obligatoire',
            'prenom.required' => 'Le prénom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.unique' => 'Cet email est déjà utilisé',
            'telephone.required' => 'Le téléphone est obligatoire',
            'adresse.required' => 'L\'adresse est obligatoire',
            'date_naissance.required' => 'La date de naissance est obligatoire',
            'lieu_naissance.required' => 'Le lieu de naissance est obligatoire',
            'sexe.required' => 'Le sexe est obligatoire',
            'specialite.required' => 'La spécialité est obligatoire',
            'diplome.required' => 'Le diplôme est obligatoire',
            'annees_experience.min' => 'Les années d\'expérience ne peuvent pas être négatives',
            'annees_experience.max' => 'Les années d\'expérience ne peuvent pas dépasser 50',
            'matieres.required' => 'Au moins une matière doit être sélectionnée',
            'matieres.min' => 'Au moins une matière doit être sélectionnée',
            'matieres.*.exists' => 'Une des matières sélectionnées n\'existe pas',
        ];
    }
}

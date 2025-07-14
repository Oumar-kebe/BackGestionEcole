<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EleveRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user() && auth()->user()->role === 'administrateur';
    }

    public function rules()
    {
        return [
            // Informations personnelles
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'required|string|max:255',
            'sexe' => 'required|in:M,F',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'required|string',

            // Informations médicales
            'nationalite' => 'required|string|max:100',
            'groupe_sanguin' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'allergies' => 'nullable|string',
            'maladies' => 'nullable|string',
            'personne_urgence_nom' => 'required|string|max:255',
            'personne_urgence_telephone' => 'required|string|max:20',

            // Inscription
            'classe_id' => 'required|exists:classes,id',

            // Informations du parent/tuteur
            'parent_nom' => 'required|string|max:255',
            'parent_prenom' => 'required|string|max:255',
            'parent_email' => 'required|email',
            'parent_telephone' => 'required|string|max:20',
            'lien_parente' => 'required|in:pere,mere,tuteur,autre',
            'parent_profession' => 'nullable|string|max:255',
            'parent_lieu_travail' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le nom est obligatoire',
            'prenom.required' => 'Le prénom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.unique' => 'Cet email est déjà utilisé',
            'date_naissance.required' => 'La date de naissance est obligatoire',
            'date_naissance.before' => 'La date de naissance doit être dans le passé',
            'lieu_naissance.required' => 'Le lieu de naissance est obligatoire',
            'sexe.required' => 'Le sexe est obligatoire',
            'adresse.required' => 'L\'adresse est obligatoire',
            'nationalite.required' => 'La nationalité est obligatoire',
            'groupe_sanguin.in' => 'Le groupe sanguin n\'est pas valide',
            'personne_urgence_nom.required' => 'Le nom de la personne à contacter en urgence est obligatoire',
            'personne_urgence_telephone.required' => 'Le téléphone de la personne à contacter en urgence est obligatoire',
            'classe_id.required' => 'La classe est obligatoire',
            'classe_id.exists' => 'La classe sélectionnée n\'existe pas',
            'parent_nom.required' => 'Le nom du parent est obligatoire',
            'parent_prenom.required' => 'Le prénom du parent est obligatoire',
            'parent_email.required' => 'L\'email du parent est obligatoire',
            'parent_telephone.required' => 'Le téléphone du parent est obligatoire',
            'lien_parente.required' => 'Le lien de parenté est obligatoire',
        ];
    }
}

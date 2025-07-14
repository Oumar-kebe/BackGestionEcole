<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClasseRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user() && auth()->user()->role === 'administrateur';
    }

    public function rules()
    {
        return [
            'nom' => 'required|string|max:255',
            'niveau_id' => 'required|exists:niveaux,id',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
            'capacite' => 'required|integer|min:10|max:100',
            'actif' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le nom de la classe est obligatoire',
            'niveau_id.required' => 'Le niveau est obligatoire',
            'niveau_id.exists' => 'Le niveau sélectionné n\'existe pas',
            'annee_scolaire_id.required' => 'L\'année scolaire est obligatoire',
            'annee_scolaire_id.exists' => 'L\'année scolaire sélectionnée n\'existe pas',
            'capacite.required' => 'La capacité est obligatoire',
            'capacite.min' => 'La capacité minimale est de 10 élèves',
            'capacite.max' => 'La capacité maximale est de 100 élèves',
        ];
    }
}

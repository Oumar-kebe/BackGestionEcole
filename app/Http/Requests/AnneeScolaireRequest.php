<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnneeScolaireRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user() && auth()->user()->role === 'administrateur';
    }

    public function rules()
    {
        $rules = [
            'libelle' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'actuelle' => 'boolean'
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['libelle'] .= '|unique:annees_scolaires,libelle,' . $this->route('annee_scolaire');
        } else {
            $rules['libelle'] .= '|unique:annees_scolaires';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire',
            'libelle.unique' => 'Cette année scolaire existe déjà',
            'date_debut.required' => 'La date de début est obligatoire',
            'date_fin.required' => 'La date de fin est obligatoire',
            'date_fin.after' => 'La date de fin doit être après la date de début',
        ];
    }
}

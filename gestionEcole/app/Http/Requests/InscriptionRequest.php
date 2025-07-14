<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Inscription;
use App\Models\Classe;

class InscriptionRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user() && auth()->user()->role === 'administrateur';
    }

    public function rules()
    {
        return [
            'eleve_id' => 'required|exists:eleves,id',
            'classe_id' => 'required|exists:classes,id',
            'date_inscription' => 'nullable|date',
            'observations' => 'nullable|string|max:500'
        ];
    }

    public function messages()
    {
        return [
            'eleve_id.required' => 'L\'élève est obligatoire',
            'eleve_id.exists' => 'L\'élève sélectionné n\'existe pas',
            'classe_id.required' => 'La classe est obligatoire',
            'classe_id.exists' => 'La classe sélectionnée n\'existe pas',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->eleve_id && $this->classe_id) {
                // Vérifier si l'élève n'est pas déjà inscrit
                $classe = Classe::find($this->classe_id);
                $inscriptionExistante = Inscription::where('eleve_id', $this->eleve_id)
                    ->where('annee_scolaire_id', $classe->annee_scolaire_id)
                    ->where('statut', 'en_cours')
                    ->exists();

                if ($inscriptionExistante) {
                    $validator->errors()->add('eleve_id', 'Cet élève est déjà inscrit pour cette année scolaire');
                }

                // Vérifier la capacité de la classe
                if ($classe->estPleine()) {
                    $validator->errors()->add('classe_id', 'Cette classe est déjà pleine');
                }
            }
        });
    }
}

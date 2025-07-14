<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Periode;

class PeriodeRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user() && auth()->user()->role === 'administrateur';
    }

    public function rules()
    {
        return [
            'nom' => 'required|string|max:255',
            'type' => 'required|in:trimestre,semestre',
            'ordre' => 'required|integer|min:1|max:6',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'actuelle' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le nom de la période est obligatoire',
            'type.required' => 'Le type de période est obligatoire',
            'type.in' => 'Le type doit être trimestre ou semestre',
            'ordre.required' => 'L\'ordre est obligatoire',
            'ordre.min' => 'L\'ordre minimum est 1',
            'ordre.max' => 'L\'ordre maximum est 6',
            'annee_scolaire_id.required' => 'L\'année scolaire est obligatoire',
            'date_debut.required' => 'La date de début est obligatoire',
            'date_fin.required' => 'La date de fin est obligatoire',
            'date_fin.after' => 'La date de fin doit être après la date de début',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->date_debut && $this->date_fin && $this->annee_scolaire_id) {
                // Vérifier le chevauchement de dates
                $query = Periode::where('annee_scolaire_id', $this->annee_scolaire_id)
                    ->where(function($q) {
                        $q->whereBetween('date_debut', [$this->date_debut, $this->date_fin])
                            ->orWhereBetween('date_fin', [$this->date_debut, $this->date_fin])
                            ->orWhere(function($q2) {
                                $q2->where('date_debut', '<=', $this->date_debut)
                                    ->where('date_fin', '>=', $this->date_fin);
                            });
                    });

                // Exclure la période courante en cas de mise à jour
                if ($this->route('periode')) {
                    $query->where('id', '!=', $this->route('periode'));
                }

                if ($query->exists()) {
                    $validator->errors()->add('date_debut', 'Les dates se chevauchent avec une période existante');
                }
            }
        });
    }
}

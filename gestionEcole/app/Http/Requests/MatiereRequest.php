<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatiereRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user() && auth()->user()->role === 'administrateur';
    }

    public function rules()
    {
        $rules = [
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'coefficient' => 'required|numeric|min:0.5|max:10',
            'niveau_id' => 'required|exists:niveaux,id',
            'actif' => 'boolean'
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['code'] .= '|unique:matieres,code,' . $this->route('matiere');
        } else {
            $rules['code'] .= '|unique:matieres';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le nom de la matière est obligatoire',
            'code.required' => 'Le code est obligatoire',
            'code.unique' => 'Ce code existe déjà',
            'coefficient.required' => 'Le coefficient est obligatoire',
            'coefficient.min' => 'Le coefficient minimum est 0.5',
            'coefficient.max' => 'Le coefficient maximum est 10',
            'niveau_id.required' => 'Le niveau est obligatoire',
            'niveau_id.exists' => 'Le niveau sélectionné n\'existe pas',
        ];
    }
}

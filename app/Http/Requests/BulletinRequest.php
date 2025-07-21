<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulletinRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user() && auth()->user()->role === 'administrateur';
    }

    public function rules()
    {
        return [
            'periode_id' => 'required|exists:periodes,id',
            'classe_id' => 'nullable|exists:classes,id|required_without:eleve_id',
            'eleve_id' => 'nullable|exists:eleves,id|required_without:classe_id',
        ];
    }

    public function messages()
    {
        return [
            'periode_id.required' => 'La période est obligatoire',
            'periode_id.exists' => 'La période sélectionnée n\'existe pas',
            'classe_id.exists' => 'La classe sélectionnée n\'existe pas',
            'classe_id.required_without' => 'Veuillez spécifier une classe ou un élève',
            'eleve_id.exists' => 'L\'élève sélectionné n\'existe pas',
            'eleve_id.required_without' => 'Veuillez spécifier une classe ou un élève',
        ];
    }
}

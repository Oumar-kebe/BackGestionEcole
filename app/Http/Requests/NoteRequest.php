<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user() && auth()->user()->role === 'enseignant';
    }

    public function rules()
    {
        if ($this->route()->getName() === 'notes.saisie-groupee') {
            return [
                'classe_id' => 'required|exists:classes,id',
                'matiere_id' => 'required|exists:matieres,id',
                'periode_id' => 'required|exists:periodes,id',
                'notes' => 'required|array|min:1',
                'notes.*.eleve_id' => 'required|exists:eleves,id',
                'notes.*.note_devoir1' => 'nullable|numeric|min:0|max:20',
                'notes.*.note_devoir2' => 'nullable|numeric|min:0|max:20',
                'notes.*.note_composition' => 'nullable|numeric|min:0|max:20',
            ];
        }

        return [
            'eleve_id' => 'required|exists:eleves,id',
            'matiere_id' => 'required|exists:matieres,id',
            'periode_id' => 'required|exists:periodes,id',
            'note_devoir1' => 'nullable|numeric|min:0|max:20',
            'note_devoir2' => 'nullable|numeric|min:0|max:20',
            'note_composition' => 'nullable|numeric|min:0|max:20',
        ];
    }

    public function messages()
    {
        return [
            'eleve_id.required' => 'L\'élève est obligatoire',
            'eleve_id.exists' => 'L\'élève sélectionné n\'existe pas',
            'matiere_id.required' => 'La matière est obligatoire',
            'matiere_id.exists' => 'La matière sélectionnée n\'existe pas',
            'periode_id.required' => 'La période est obligatoire',
            'periode_id.exists' => 'La période sélectionnée n\'existe pas',
            'note_devoir1.min' => 'La note doit être entre 0 et 20',
            'note_devoir1.max' => 'La note doit être entre 0 et 20',
            'note_devoir2.min' => 'La note doit être entre 0 et 20',
            'note_devoir2.max' => 'La note doit être entre 0 et 20',
            'note_composition.min' => 'La note doit être entre 0 et 20',
            'note_composition.max' => 'La note doit être entre 0 et 20',
            'classe_id.required' => 'La classe est obligatoire',
            'notes.required' => 'Les notes sont obligatoires',
            'notes.min' => 'Au moins une note doit être saisie',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Vérifier qu'au moins une note est fournie
            if (!isset($data['note_devoir1']) &&
                !isset($data['note_devoir2']) &&
                !isset($data['note_composition'])) {
                $validator->errors()->add('notes', 'Au moins une note doit être saisie');
            }
        });
    }
}

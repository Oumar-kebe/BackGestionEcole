<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'ancien_password' => 'required',
            'nouveau_password' => 'required|min:6|confirmed|different:ancien_password',
        ];
    }

    public function messages()
    {
        return [
            'ancien_password.required' => 'L\'ancien mot de passe est obligatoire',
            'nouveau_password.required' => 'Le nouveau mot de passe est obligatoire',
            'nouveau_password.min' => 'Le nouveau mot de passe doit contenir au moins 6 caractères',
            'nouveau_password.confirmed' => 'Les mots de passe ne correspondent pas',
            'nouveau_password.different' => 'Le nouveau mot de passe doit être différent de l\'ancien',
        ];
    }
}

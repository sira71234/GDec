<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
{
    /**
     * Autorise la requête : la connexion est ouverte à tout visiteur.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation des identifiants + code admin à la connexion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identifiant' => ['required', 'string'],
            'mot_de_passe' => ['required', 'string'],
            'code_admin' => ['required', 'string'],
        ];
    }

    /**
     * Messages d'erreur personnalisés en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifiant.required' => "L'identifiant est obligatoire.",
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
            'code_admin.required' => 'Le code administrateur est obligatoire.',
        ];
    }
}

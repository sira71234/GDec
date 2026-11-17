<?php

namespace App\Http\Requests;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminStoreRequest extends FormRequest
{
    /**
     * Réservé au super administrateur : la route est déjà protégée par le
     * middleware, la requête ne fait que valider les données.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $administrateur = $this->route('administrateur');
        $creation = $administrateur === null;

        return [
            'identifiant' => [
                'required',
                'string',
                'min:3',
                'max:50',
                Rule::unique('admins', 'identifiant')->ignore($administrateur?->id),
            ],
            'mot_de_passe' => [$creation ? 'required' : 'nullable', 'string', 'min:8', 'max:255'],
            'code_admin' => [$creation ? 'required' : 'nullable', 'digits:6'],
            'role' => ['required', Rule::in([Admin::ROLE_ADMIN, Admin::ROLE_SUPER_ADMIN])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifiant.required' => "L'identifiant est obligatoire.",
            'identifiant.min' => "L'identifiant doit contenir au moins 3 caractères.",
            'identifiant.unique' => 'Cet identifiant est déjà utilisé.',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'code_admin.required' => 'Le code administrateur est obligatoire.',
            'code_admin.digits' => 'Le code administrateur doit contenir exactement 6 chiffres.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle doit être « admin » ou « super_admin ».',
        ];
    }
}

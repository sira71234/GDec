<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaterielRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Une route de mise à jour porte un paramètre de route ({materiel}).
        $creation = count($this->route()->parameters()) === 0;

        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'prix_unitaire' => ['required', 'numeric', 'min:0'],
            'quantite_stock' => ['required', 'integer', 'min:0'],
            'photo' => [
                $creation ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrestationDecorationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Une route de mise à jour porte un paramètre de route ({prestation}).
        $creation = count($this->route()->parameters()) === 0;

        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'prix' => ['required', 'numeric', 'min:0'],
            'photo' => [
                $creation ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}

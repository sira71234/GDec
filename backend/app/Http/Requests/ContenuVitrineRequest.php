<?php

namespace App\Http\Requests;

use App\Models\ContenuVitrine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContenuVitrineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Une route de mise à jour porte un paramètre de route ({contenu}).
        $creation = count($this->route()->parameters()) === 0;

        return [
            'section' => [
                'required',
                Rule::in([
                    ContenuVitrine::SECTION_GALERIE,
                    ContenuVitrine::SECTION_LIBRE,
                ]),
            ],
            'type' => ['required', Rule::in([ContenuVitrine::TYPE_TEXTE, ContenuVitrine::TYPE_IMAGE])],
            'titre' => ['nullable', 'string', 'max:255'],
            'valeur' => ['nullable', 'string'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'image' => [
                Rule::requiredIf(fn () => $this->input('type') === ContenuVitrine::TYPE_IMAGE && $creation),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}

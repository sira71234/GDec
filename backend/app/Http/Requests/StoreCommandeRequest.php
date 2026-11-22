<?php

namespace App\Http\Requests;

use App\Models\Materiel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCommandeRequest extends FormRequest
{
    /**
     * Autorise la requête : la soumission d'une commande est ouverte au public.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation des champs de la commande (matériel, déco,
     * coordonnées du client et pièce d'identité).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $besoinLocation = fn (): bool => in_array(
            $this->input('type_prestation'),
            ['location', 'les_deux'],
            true,
        );

        $besoinDecoration = fn (): bool => in_array(
            $this->input('type_prestation'),
            ['decoration', 'les_deux'],
            true,
        );

        return [
            'type_prestation' => ['required', Rule::in(['location', 'decoration', 'les_deux'])],

            // Coordonnées du client.
            'client' => ['required', 'array'],
            'client.nom' => ['required', 'string', 'max:255'],
            'client.telephone' => ['required', 'string', 'max:50'],
            'client.email' => ['nullable', 'email', 'max:255'],
            'client.adresse' => ['required', 'string', 'max:255'],

            // Location : dates + pièce d'identité obligatoires.
            'date_debut_location' => [Rule::requiredIf($besoinLocation), 'nullable', 'date', 'after_or_equal:today'],
            'date_fin_location' => [Rule::requiredIf($besoinLocation), 'nullable', 'date', 'after_or_equal:date_debut_location'],
            'piece_identite_type' => [Rule::requiredIf($besoinLocation), 'nullable', 'string', 'max:255'],
            'piece_identite_photo' => [Rule::requiredIf($besoinLocation), 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],

            // Décoration : type d'événement, date et durée obligatoires.
            'type_evenement' => [Rule::requiredIf($besoinDecoration), 'nullable', 'string', 'max:255'],
            'date_evenement' => [Rule::requiredIf($besoinDecoration), 'nullable', 'date', 'after_or_equal:today'],
            'duree_decoration' => [Rule::requiredIf($besoinDecoration), 'nullable', 'integer', 'min:1'],
            'theme' => ['nullable', 'string', 'max:2000'],
            'couleurs' => ['nullable', 'string', 'max:255'],
            'nombre_personnes' => ['nullable', 'integer', 'min:1'],
            'complement' => ['nullable', 'string', 'max:2000'],

            // Matériel loué (au moins une ligne si location).
            'materiels' => [Rule::requiredIf($besoinLocation), 'array'],
            'materiels.*.id' => ['required', 'integer', 'distinct', 'exists:materiels,id'],
            // Aucun plafond : le client loue la quantité qu'il souhaite.
            'materiels.*.quantite' => ['required', 'integer', 'min:1'],

            // Prestations de décoration (facultatif, quantité facultative).
            'prestations_decoration' => ['nullable', 'array'],
            'prestations_decoration.*.id' => ['required', 'integer', 'exists:prestations_decoration,id'],
            'prestations_decoration.*.quantite' => ['nullable', 'integer', 'min:1'],

            // Éléments à décorer sélectionnés.
            'elements_decor' => ['nullable', 'array'],
            'elements_decor.*' => ['integer', 'exists:elements_decor,id'],
        ];
    }

    /**
     * Validations complémentaires après les règles principales.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $materiels = $this->input('materiels', []);
                if (! is_array($materiels)) {
                    return;
                }

                // Aucune limite de quantité : seul le stock épuisé bloque la
                // location. Le stock n'est pas un plafond, une simple
                // disponibilité (un article peut être loué en 10 exemplaires
                // même s'il n'en reste que 2 en rayon).
                $idsDemandes = [];
                foreach ($materiels as $ligne) {
                    if (! is_array($ligne)) {
                        continue;
                    }
                    $id = $ligne['id'] ?? null;
                    $quantite = isset($ligne['quantite']) ? (int) $ligne['quantite'] : 0;
                    if ($id && $quantite > 0) {
                        $idsDemandes[$id] = $id;
                    }
                }

                if (empty($idsDemandes)) {
                    return;
                }

                $articles = Materiel::whereIn('id', $idsDemandes)
                    ->get(['id', 'nom', 'quantite_stock'])
                    ->keyBy('id');

                foreach ($idsDemandes as $id) {
                    $article = $articles->get($id);
                    if ($article === null) {
                        continue;
                    }
                    if ((int) ($article->quantite_stock ?? 0) <= 0) {
                        $validator->errors()->add(
                            'materiels',
                            "Le matériel « {$article->nom} » n'est plus disponible.",
                        );
                    }
                }
            },
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
            'type_prestation.required' => 'Le type de prestation est obligatoire.',
            'client.required' => 'Les coordonnées du client sont obligatoires.',
            'client.nom.required' => 'Le nom du client est obligatoire.',
            'client.telephone.required' => 'Le téléphone du client est obligatoire.',
            'client.adresse.required' => 'L’adresse du client est obligatoire.',
            'date_debut_location.required_if' => 'La date de début de location est obligatoire.',
            'date_fin_location.required_if' => 'La date de fin de location est obligatoire.',
            'date_fin_location.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'piece_identite_type.required_if' => "Le type de pièce d'identité est obligatoire pour une location.",
            'piece_identite_photo.required_if' => "La photo de la pièce d'identité est obligatoire pour une location.",
            'type_evenement.required_if' => "Le type d'événement est obligatoire pour la décoration.",
            'date_evenement.required_if' => "La date de l'événement est obligatoire pour la décoration.",
            'date_evenement.after_or_equal' => "La date de l'événement doit être aujourd'hui ou ultérieure.",
            'duree_decoration.required_if' => 'La durée de la décoration est obligatoire.',
            'duree_decoration.min' => 'La durée de la décoration doit être d\'au moins un jour.',
            'client.email.email' => "L'adresse email est invalide.",
            'materiels.required_if' => 'Sélectionnez au moins un matériel à louer.',
        ];
    }
}

<?php

namespace App\Services;

use App\Models\ContenuVitrine;

/**
 * Assemble le contenu de la vitrine (page d'accueil) sous une forme prête
 * à consommer par le front : bannière, galerie et sections libres.
 */
class VitrineService
{
    /**
     * @return array{banniere: array<string, ?string>, galerie: array<int, array<string, mixed>>, sections: array<int, array<string, mixed>>, reglages: array{afficher_visuels_defaut: bool, visuels_defaut_masques: array<int, string>}}
     */
    public function representation(): array
    {
        $contenus = ContenuVitrine::orderBy('ordre')->get();

        $banniere = [
            'titre' => null,
            'sous_titre' => null,
            'image' => null,
        ];

        foreach ($contenus->where('section', ContenuVitrine::SECTION_BANNIERE) as $item) {
            if (array_key_exists($item->cle, $banniere)) {
                $banniere[$item->cle] = $item->valeur;
            }
        }

        $galerie = $contenus
            ->where('section', ContenuVitrine::SECTION_GALERIE)
            ->map(fn (ContenuVitrine $item): array => [
                'id' => $item->id,
                'titre' => $item->titre,
                'image' => $item->valeur,
                'ordre' => $item->ordre,
            ])
            ->values()
            ->all();

        $sections = $contenus
            ->where('section', ContenuVitrine::SECTION_LIBRE)
            ->map(fn (ContenuVitrine $item): array => [
                'id' => $item->id,
                'titre' => $item->titre,
                'texte' => $item->valeur,
                'ordre' => $item->ordre,
            ])
            ->values()
            ->all();

        // Les visuels livrés avec le site restent affichés tant que
        // l'administrateur ne les désactive pas explicitement. Il peut aussi
        // masquer seulement certains d'entre eux, individuellement.
        $reglageVisuels = $contenus
            ->where('section', ContenuVitrine::SECTION_REGLAGES)
            ->firstWhere('cle', 'afficher_visuels_defaut');

        $reglageMasques = $contenus
            ->where('section', ContenuVitrine::SECTION_REGLAGES)
            ->firstWhere('cle', 'visuels_defaut_masques');

        return [
            'banniere' => $banniere,
            'galerie' => $galerie,
            'sections' => $sections,
            'reglages' => [
                'afficher_visuels_defaut' => $reglageVisuels?->valeur !== '0',
                'visuels_defaut_masques' => $this->listeMasques($reglageMasques?->valeur),
            ],
        ];
    }

    /**
     * Décode la liste (JSON) des visuels d'origine masqués.
     *
     * @return array<int, string>
     */
    private function listeMasques(?string $valeur): array
    {
        if ($valeur === null || $valeur === '') {
            return [];
        }

        $liste = json_decode($valeur, true);

        if (! is_array($liste)) {
            return [];
        }

        return array_values(array_filter($liste, 'is_string'));
    }
}

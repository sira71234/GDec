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
     * @return array{banniere: array<string, ?string>, galerie: array<int, array<string, mixed>>, sections: array<int, array<string, mixed>>, reglages: array{afficher_visuels_defaut: bool}}
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
        // l'administrateur ne les désactive pas explicitement.
        $reglageVisuels = $contenus
            ->where('section', ContenuVitrine::SECTION_REGLAGES)
            ->firstWhere('cle', 'afficher_visuels_defaut');

        return [
            'banniere' => $banniere,
            'galerie' => $galerie,
            'sections' => $sections,
            'reglages' => [
                'afficher_visuels_defaut' => $reglageVisuels?->valeur !== '0',
            ],
        ];
    }
}

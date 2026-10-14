<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Gère le stockage et la suppression des images du catalogue et de la vitrine
 * sur le disque "public" (accessible en lecture via /storage).
 */
class ImageStorageService
{
    /** Enregistre un fichier image et retourne son chemin relatif. */
    public function store(UploadedFile $fichier, string $dossier): string
    {
        return $fichier->store($dossier, 'public');
    }

    /** Supprime une image existante si elle est présente sur le disque. */
    public function delete(?string $chemin): void
    {
        if ($chemin !== null && $chemin !== '' && Storage::disk('public')->exists($chemin)) {
            Storage::disk('public')->delete($chemin);
        }
    }

    /** Remplace une image : enregistre la nouvelle et supprime l'ancienne. */
    public function replace(UploadedFile $fichier, ?string $ancienChemin, string $dossier): string
    {
        $nouveauChemin = $this->store($fichier, $dossier);
        $this->delete($ancienChemin);

        return $nouveauChemin;
    }
}

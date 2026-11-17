// Visuels livrés avec le site (dossier src/assets/images).
// Ils ne vivent pas en base : l'administration les masque individuellement
// en enregistrant leur nom de fichier dans les réglages de la vitrine.

const fichiers = import.meta.glob("../assets/images/*.{jpg,jpeg,png,webp}", {
  eager: true,
});

export const VISUELS_DEFAUT = Object.entries(fichiers).map(
  ([chemin, module]) => {
    const fichier = chemin.split("/").pop();
    const base = fichier.split(".")[0];

    return {
      // Identifiant stable enregistré côté serveur.
      fichier,
      nom: base.replace(/_/g, " "),
      photo: module.default,
    };
  },
);

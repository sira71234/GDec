import { useEffect, useState } from "react";
import ArticleCard from "../composants/ArticleCards.jsx";
import Reveal from "../composants/Reveal.jsx";
import { getVitrine } from "../services/catalogueService";
import { storageUrl } from "../services/api";
import { VISUELS_DEFAUT } from "../services/visuelsDefaut";

// Valeurs utilisées tant que l'administrateur n'a rien personnalisé.
const BANNIERE_PAR_DEFAUT = {
  titre: "Location & Décoration pour tous vos événements",
  sous_titre:
    "Des solutions clé en main pour réussir votre mariage, anniversaire, ou événement d'entreprise.",
};

function Home() {
  const [vitrine, setVitrine] = useState(null);

  useEffect(() => {
    getVitrine()
      .then(setVitrine)
      .catch(() => setVitrine(null));
  }, []);

  // Visuels livrés avec le site, éventuellement masqués un par un depuis
  // l'espace d'administration.
  const visuelsMasques = vitrine?.reglages?.visuels_defaut_masques ?? [];
  const articlesParDefaut = VISUELS_DEFAUT.filter(
    (visuel) => !visuelsMasques.includes(visuel.fichier),
  );

  const banniere = {
    titre: vitrine?.banniere?.titre || BANNIERE_PAR_DEFAUT.titre,
    sous_titre: vitrine?.banniere?.sous_titre || BANNIERE_PAR_DEFAUT.sous_titre,
    image: vitrine?.banniere?.image ? storageUrl(vitrine.banniere.image) : null,
  };

  // Les images ajoutées par l'administrateur s'ajoutent aux visuels d'origine
  // tant que le réglage "afficher les visuels par défaut" reste actif.
  const galerieAdmin = (vitrine?.galerie ?? []).map((item) => ({
    photo: storageUrl(item.image),
    nom: item.titre || "",
  }));

  const afficherVisuelsDefaut =
    vitrine?.reglages?.afficher_visuels_defaut ?? true;

  const galerie = [
    ...galerieAdmin,
    ...(afficherVisuelsDefaut ? articlesParDefaut : []),
  ];

  const sections = vitrine?.sections ?? [];

  return (
    <>
      <section
        className="relative bg-emerald-800 text-white text-center py-6 px-4 bg-cover bg-center"
        style={banniere.image ? { backgroundImage: `url(${banniere.image})` } : undefined}
      >
        {banniere.image && <div className="absolute inset-0 bg-emerald-900/70" />}
        <div className="relative">
          <h1 className="text-3xl md:text-5xl font-bold mb-4">{banniere.titre}</h1>
          <p className="text-lg max-w-2xl mx-auto">{banniere.sous_titre}</p>
        </div>
      </section>

      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-6">
        {galerie.map((article, index) => (
          <Reveal key={article.photo ?? index} delai={(index % 6) * 70} className="h-full">
            <ArticleCard
              photo={article.photo}
              nom={article.nom}
            />
          </Reveal>
        ))}
      </div>

      {sections.map((bloc) => (
        <section key={bloc.id} className="max-w-3xl mx-auto px-6 pb-6">
          {bloc.titre && (
            <h2 className="text-2xl font-bold text-emerald-800 mb-2">{bloc.titre}</h2>
          )}
          {bloc.texte && <p className="text-gray-700">{bloc.texte}</p>}
        </section>
      ))}
    </>
  );
}

export default Home;

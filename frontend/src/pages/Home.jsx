import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import ArticleCard from "../composants/ArticleCards.jsx";
import { getVitrine } from "../services/catalogueService";
import { storageUrl } from "../services/api";

const images = import.meta.glob("../assets/images/*.{jpg,jpeg,png,webp}", { eager: true });

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

  // Repli sur les visuels livrés avec le site si la galerie admin est vide.
  const articlesParDefaut = Object.entries(images).map(([chemin, module]) => {
    const nomFichier = chemin.split("/").pop().split(".")[0];
    return { photo: module.default, nom: nomFichier.replace(/_/g, " ") };
  });

  const banniere = {
    titre: vitrine?.banniere?.titre || BANNIERE_PAR_DEFAUT.titre,
    sous_titre: vitrine?.banniere?.sous_titre || BANNIERE_PAR_DEFAUT.sous_titre,
    image: vitrine?.banniere?.image ? storageUrl(vitrine.banniere.image) : null,
  };

  const galerie = vitrine?.galerie?.length
    ? vitrine.galerie.map((item) => ({
        photo: storageUrl(item.image),
        nom: item.titre || "",
      }))
    : articlesParDefaut;

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
          <p className="text-lg mb-6 max-w-2xl mx-auto">{banniere.sous_titre}</p>
          <Link
            to="/commande"
            className="bg-yellow-500 text-gray-900 font-semibold px-6 py-3 rounded-lg inline-block"
          >
            Commander maintenant
          </Link>
        </div>
      </section>

      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-6">
        {galerie.map((article, index) => (
          <ArticleCard
            key={article.photo ?? index}
            photo={article.photo}
            nom={article.nom}
          />
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

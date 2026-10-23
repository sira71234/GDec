import { useState } from "react";
import { storageUrl } from "../services/api";
import placeholder from "../assets/hero.png";

/**
 * Carte d'article réutilisable (location et décoration).
 * - `article`   : { id, nom, description, photo, ... }
 * - `prix`      : prix affiché (prix_unitaire pour le matériel, prix pour une prestation)
 * - `quantite` / `onQuantiteChange` : mode contrôlé (facultatif)
 * - `onAdd`     : callback d'ajout au panier (facultatif)
 * - `selected`  : met en évidence la carte (facultatif)
 */
function ArticleCard({
  article,
  prix,
  quantite,
  onQuantiteChange,
  onAdd,
  selected = false,
}) {
  const [quantiteInterne, setQuantiteInterne] = useState(1);
  const [agrandie, setAgrandie] = useState(false);

  const quantiteCourante = quantite ?? quantiteInterne;
  const definirQuantite = onQuantiteChange ?? setQuantiteInterne;
  const image = storageUrl(article.photo) || placeholder;

  const basculerPhoto = (event) => {
    event.currentTarget.src = placeholder;
  };

  return (
    <div
      className={`flex flex-col gap-3 p-4 border-2 rounded-xl bg-white shadow-sm transition hover:shadow-md ${
        selected ? "border-emerald-600" : "border-gray-200"
      }`}
    >
      <div className="relative w-full aspect-[4/3] overflow-hidden rounded-lg bg-emerald-50">
        <img
          className="w-full h-full object-cover transition duration-300 hover:scale-105 cursor-zoom-in"
          src={image}
          alt={article.nom}
          loading="lazy"
          onClick={() => setAgrandie(true)}
          onError={basculerPhoto}
        />
      </div>

      {agrandie && (
        <div
          className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 cursor-zoom-out"
          onClick={() => setAgrandie(false)}
          role="presentation"
        >
          <img
            src={image}
            alt={article.nom}
            className="max-h-[85vh] max-w-full object-contain rounded-lg bg-white"
          />
        </div>
      )}

      <div className="flex flex-col gap-1 grow">
        <h3 className="font-semibold text-gray-800">{article.nom}</h3>
        {article.description && (
          <p className="text-sm text-gray-500 line-clamp-2">{article.description}</p>
        )}
        {article.quantite_stock !== undefined && (
          <p className="text-xs text-gray-400">
            Stock : {article.quantite_stock}
          </p>
        )}
        <p className="text-green-700 font-bold mt-auto pt-2">
          {Number(prix).toLocaleString("fr-FR")} FCFA
        </p>
      </div>

      <div className="flex items-center gap-2">
        <input
          className="w-16 border border-gray-300 rounded-md px-2 py-1 text-center"
          type="number"
          min="1"
          value={quantiteCourante}
          onChange={(event) => definirQuantite(Number(event.target.value))}
        />
        {onAdd && (
          <button
            type="button"
            onClick={() => onAdd(quantiteCourante)}
            className="grow bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold px-3 py-2 rounded-lg transition"
          >
            Ajouter
          </button>
        )}
      </div>
    </div>
  );
}

export default ArticleCard;

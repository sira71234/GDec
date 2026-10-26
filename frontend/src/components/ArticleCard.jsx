import { useState } from "react";
import { storageUrl } from "../services/api";
import placeholder from "../assets/hero.png";

/**
 * Carte d'article réutilisable (location et décoration).
 * - `article`   : { id, nom, description, photo, quantite_stock, ... }
 * - `prix`      : prix affiché (prix_unitaire pour le matériel, prix pour une prestation)
 * - `quantite`  : quantité actuellement dans le panier (0 = absent)
 * - `max`       : quantité maximale autorisée (stock disponible), facultatif
 * - `onAdd`     : ajout au panier avec la quantité choisie
 * - `onModifier`: modification de la quantité déjà au panier
 * - `onRetirer` : retrait du panier
 */
function ArticleCard({
  article,
  prix,
  quantite = 0,
  max,
  onAdd,
  onModifier,
  onRetirer,
}) {
  const [quantiteAjout, setQuantiteAjout] = useState(1);
  const [edition, setEdition] = useState(false);
  const [agrandie, setAgrandie] = useState(false);

  const enPanier = Number(quantite) > 0;
  const stock = article.quantite_stock;
  const stockDisponible = stock !== undefined && stock !== null ? Number(stock) : null;
  const rupture = stockDisponible !== null && stockDisponible <= 0;
  const maximum = max ?? stockDisponible ?? Infinity;
  const valeurAffichee = enPanier ? Number(quantite) : quantiteAjout;
  const champDesactive = enPanier && !edition;

  const image = storageUrl(article.photo) || placeholder;

  const borner = (valeur) => {
    const minimum = 1;
    const nombre = Number(valeur);
    if (Number.isNaN(nombre)) return minimum;
    return Math.max(minimum, Math.min(nombre, maximum));
  };

  const changerQuantite = (valeur) => {
    const quantiteBornee = borner(valeur);
    if (enPanier) {
      onModifier?.(quantiteBornee);
    } else {
      setQuantiteAjout(quantiteBornee);
    }
  };

  const ajouter = () => {
    if (rupture) return;
    onAdd?.(borner(quantiteAjout));
    // Le sélecteur d'ajout est réinitialisé après l'ajout.
    setQuantiteAjout(1);
    setEdition(false);
  };

  const retirer = () => {
    onRetirer?.();
    setEdition(false);
    setQuantiteAjout(1);
  };

  const basculerPhoto = (event) => {
    event.currentTarget.src = placeholder;
  };

  return (
    <div
      className={`flex flex-col gap-3 p-4 border-2 rounded-xl bg-white shadow-sm transition hover:shadow-md ${
        enPanier ? "border-emerald-600" : "border-gray-200"
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
        {stockDisponible !== null && (
          <p
            className={`text-xs font-medium ${
              rupture ? "text-red-600" : "text-gray-500"
            }`}
          >
            {rupture
              ? "Rupture de stock"
              : `Quantité disponible : ${stockDisponible}`}
          </p>
        )}
        <p className="text-green-700 font-bold mt-auto pt-2">
          {Number(prix).toLocaleString("fr-FR")} FCFA
        </p>
      </div>

      <div className="flex flex-col gap-2">
        <div className="flex items-center gap-2">
          <input
            className={`w-20 border rounded-md px-2 py-1 text-center ${
              champDesactive
                ? "border-gray-200 bg-gray-100 text-gray-600"
                : "border-gray-300"
            }`}
            type="number"
            min="1"
            max={Number.isFinite(maximum) ? maximum : undefined}
            value={valeurAffichee}
            disabled={champDesactive || rupture}
            onChange={(event) => changerQuantite(event.target.value)}
            aria-label={`Quantité de ${article.nom}`}
          />
          {enPanier ? (
            <div className="flex grow gap-2">
              <button
                type="button"
                onClick={() => setEdition(true)}
                disabled={edition}
                className="grow bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold px-3 py-2 rounded-lg transition disabled:opacity-60"
              >
                Modifier
              </button>
              <button
                type="button"
                onClick={retirer}
                className="grow bg-red-100 hover:bg-red-200 text-red-700 text-sm font-semibold px-3 py-2 rounded-lg transition"
              >
                Annuler
              </button>
            </div>
          ) : (
            <button
              type="button"
              onClick={ajouter}
              disabled={rupture}
              className="grow bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold px-3 py-2 rounded-lg transition disabled:opacity-60"
            >
              Ajouter
            </button>
          )}
        </div>
        {enPanier && !edition && (
          <p className="text-[11px] text-gray-500">
            Au panier : {quantite}. Cliquez sur « Modifier » pour changer la quantité.
          </p>
        )}
      </div>
    </div>
  );
}

export default ArticleCard;

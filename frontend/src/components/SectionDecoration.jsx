import { useEffect, useState } from "react";
import ArticleCard from "./ArticleCard";
import { usePanier } from "../context/usePanier";
import {
  getPrestationsDecoration,
  getElementsDecor,
} from "../services/catalogueService";

const TYPES_EVENEMENT = [
  "Mariage",
  "Anniversaire",
  "Baptême",
  "Séminaire",
  "Gala",
  "Funérailles",
  "Autre",
];

function SectionDecoration() {
  const {
    addPrestation,
    removePrestation,
    prestations: panierPrestations,
    elements,
    toggleElement,
    isElementSelected,
    decoration,
    updateDecoration,
  } = usePanier();

  const [prestations, setPrestations] = useState([]);
  const [elementsDecor, setElementsDecor] = useState([]);
  const [chargement, setChargement] = useState(true);
  const [erreur, setErreur] = useState(null);

  useEffect(() => {
    Promise.all([getPrestationsDecoration(), getElementsDecor()])
      .then(([listePrestations, listeElements]) => {
        setPrestations(listePrestations);
        setElementsDecor(listeElements);
      })
      .catch(() => setErreur("Impossible de charger la décoration."))
      .finally(() => setChargement(false));
  }, []);

  const quantiteDansPanier = (id) =>
    panierPrestations.find((item) => item.id === id)?.quantite ?? 0;

  return (
    <section className="bg-emerald-50 py-8 border-t-4 border-emerald-700">
      <div className="text-emerald-800 text-center py-2 px-4">
        <h2 className="text-2xl md:text-3xl font-bold mb-2">Décoration</h2>
        <p className="text-lg mb-6 max-w-2xl mx-auto">
          Composez l'ambiance de votre événement : thème, couleurs et éléments à décorer.
        </p>
      </div>

      {/* Détails de l'événement */}
      <div className="max-w-5xl mx-auto grid gap-4 md:grid-cols-2 px-6 mb-8">
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Type d'événement
          </label>
          <select
            value={decoration.type_evenement}
            onChange={(e) => updateDecoration("type_evenement", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          >
            <option value="">— Sélectionnez —</option>
            {TYPES_EVENEMENT.map((type) => (
              <option key={type} value={type}>
                {type}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Nombre d'invités
          </label>
          <input
            type="number"
            min="0"
            value={decoration.nombre_personnes}
            onChange={(e) => updateDecoration("nombre_personnes", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Date de l'événement <span className="text-red-500">*</span>
          </label>
          <input
            type="date"
            value={decoration.date_evenement}
            onChange={(e) => updateDecoration("date_evenement", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Durée (jours) <span className="text-red-500">*</span>
          </label>
          <input
            type="number"
            min="1"
            value={decoration.duree_decoration}
            onChange={(e) => updateDecoration("duree_decoration", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Thème
          </label>
          <input
            type="text"
            value={decoration.theme}
            onChange={(e) => updateDecoration("theme", e.target.value)}
            placeholder="Ex. : champêtre, bohème…"
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Couleurs
          </label>
          <input
            type="text"
            value={decoration.couleurs}
            onChange={(e) => updateDecoration("couleurs", e.target.value)}
            placeholder="Ex. : vert et blanc"
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
        <div className="md:col-span-2">
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Précisions (facultatif)
          </label>
          <textarea
            rows="2"
            value={decoration.complement}
            onChange={(e) => updateDecoration("complement", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
      </div>

      {/* Éléments à décorer */}
      <div className="max-w-5xl mx-auto px-6 mb-8">
        <h3 className="font-semibold text-emerald-800 mb-3">
          Éléments à décorer
        </h3>
        {elementsDecor.length === 0 && !chargement && (
          <p className="text-gray-500">Aucun élément disponible.</p>
        )}
        <div className="flex flex-wrap gap-3">
          {elementsDecor.map((element) => (
            <button
              key={element.id}
              type="button"
              onClick={() => toggleElement(element)}
              className={`px-4 py-2 rounded-full border transition ${
                isElementSelected(element.id)
                  ? "bg-emerald-700 text-white border-emerald-700"
                  : "bg-white text-gray-700 border-gray-300 hover:border-emerald-600"
              }`}
            >
              {element.nom}
            </button>
          ))}
        </div>
        {elements.length > 0 && (
          <p className="text-sm text-gray-500 mt-2">
            {elements.length} élément(s) sélectionné(s).
          </p>
        )}
      </div>

      {/* Prestations de décoration */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 px-6">
        {chargement && <p className="text-gray-500">Chargement des prestations…</p>}
        {erreur && <p className="text-red-600">{erreur}</p>}
        {prestations.map((prestation) => (
          <div key={prestation.id} className="relative">
            <ArticleCard
              article={prestation}
              prix={prestation.prix}
              onAdd={(quantite) => addPrestation(prestation, quantite)}
              selected={quantiteDansPanier(prestation.id) > 0}
            />
            {quantiteDansPanier(prestation.id) > 0 && (
              <>
                <span className="absolute -top-2 -right-2 bg-emerald-700 text-white text-xs font-bold rounded-full h-6 min-w-6 px-2 flex items-center justify-center">
                  {quantiteDansPanier(prestation.id)}
                </span>
                <button
                  type="button"
                  onClick={() => removePrestation(prestation.id)}
                  className="mt-1 w-full text-xs font-semibold text-red-600 hover:underline"
                >
                  Annuler l'ajout
                </button>
              </>
            )}
          </div>
        ))}
      </div>
    </section>
  );
}

export default SectionDecoration;

import { useEffect, useState } from "react";
import ArticleCard from "./ArticleCard";
import { usePanier } from "../context/usePanier";
import { getMateriels } from "../services/catalogueService";
import { verifierDisponibilite } from "../services/commandeService";

const TYPES_PIECE = [
  { valeur: "CNI", libelle: "Carte nationale d'identité" },
  { valeur: "Passeport", libelle: "Passeport" },
  { valeur: "Permis", libelle: "Permis de conduire" },
  { valeur: "Autre", libelle: "Autre pièce" },
];

function SectionLocation() {
  const {
    addMateriel,
    materiels: panierMateriels,
    location,
    updateLocation,
  } = usePanier();

  const [materiels, setMateriels] = useState([]);
  const [chargement, setChargement] = useState(true);
  const [erreur, setErreur] = useState(null);
  const [messageDispo, setMessageDispo] = useState(null);
  const [verification, setVerification] = useState(false);

  useEffect(() => {
    getMateriels()
      .then(setMateriels)
      .catch(() => setErreur("Impossible de charger le matériel."))
      .finally(() => setChargement(false));
  }, []);

  const verifierCreneau = async () => {
    if (!location.date_debut_location || !location.date_fin_location) {
      setMessageDispo({ type: "erreur", texte: "Renseignez les deux dates." });
      return;
    }
    setVerification(true);
    setMessageDispo(null);
    try {
      const reponse = await verifierDisponibilite(
        location.date_debut_location,
        location.date_fin_location,
      );
      setMessageDispo({
        type: reponse.disponible ? "succes" : "erreur",
        texte: reponse.disponible
          ? "Créneau disponible."
          : "Créneau indisponible : un événement y est déjà programmé.",
      });
    } catch {
      setMessageDispo({ type: "erreur", texte: "Vérification impossible." });
    } finally {
      setVerification(false);
    }
  };

  const quantiteDansPanier = (id) =>
    panierMateriels.find((item) => item.id === id)?.quantite ?? 0;

  return (
    <section className="bg-white py-8 border-t-4 border-emerald-700">
      <div className="text-emerald-800 text-center py-2 px-4">
        <h2 className="text-2xl md:text-3xl font-bold mb-2">Location de matériel</h2>
        <p className="text-lg mb-6 max-w-2xl mx-auto">
          Sélectionnez vos articles et précisez les dates de votre événement.
        </p>
      </div>

      {/* Dates + pièce d'identité */}
      <div className="max-w-5xl mx-auto grid gap-4 md:grid-cols-3 px-6 mb-8">
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Date de début
          </label>
          <input
            type="date"
            value={location.date_debut_location}
            onChange={(e) => updateLocation("date_debut_location", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Date de fin
          </label>
          <input
            type="date"
            value={location.date_fin_location}
            onChange={(e) => updateLocation("date_fin_location", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
        <div className="flex items-end">
          <button
            type="button"
            onClick={verifierCreneau}
            disabled={verification}
            className="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-4 py-2 rounded-lg transition disabled:opacity-60"
          >
            {verification ? "Vérification…" : "Vérifier la disponibilité"}
          </button>
        </div>
      </div>

      {messageDispo && (
        <p
          className={`text-center mx-6 mb-6 font-medium ${
            messageDispo.type === "succes" ? "text-emerald-700" : "text-red-600"
          }`}
        >
          {messageDispo.texte}
        </p>
      )}

      {/* Pièce d'identité */}
      <div className="max-w-5xl mx-auto grid gap-4 md:grid-cols-2 px-6 mb-8">
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Type de pièce d'identité
          </label>
          <select
            value={location.piece_identite_type}
            onChange={(e) => updateLocation("piece_identite_type", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          >
            <option value="">— Sélectionnez —</option>
            {TYPES_PIECE.map((type) => (
              <option key={type.valeur} value={type.valeur}>
                {type.libelle}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Photo de la pièce (JPG/PNG)
          </label>
          <input
            type="file"
            accept="image/*"
            onChange={(e) =>
              updateLocation("piece_identite_photo", e.target.files?.[0] ?? null)
            }
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
      </div>

      {/* Grille du matériel */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 px-6">
        {chargement && <p className="text-gray-500">Chargement du matériel…</p>}
        {erreur && <p className="text-red-600">{erreur}</p>}
        {materiels.map((materiel) => (
          <div key={materiel.id} className="relative">
            <ArticleCard
              article={materiel}
              prix={materiel.prix_unitaire}
              onAdd={(quantite) => addMateriel(materiel, quantite)}
              selected={quantiteDansPanier(materiel.id) > 0}
            />
            {quantiteDansPanier(materiel.id) > 0 && (
              <span className="absolute -top-2 -right-2 bg-emerald-700 text-white text-xs font-bold rounded-full h-6 min-w-6 px-2 flex items-center justify-center">
                {quantiteDansPanier(materiel.id)}
              </span>
            )}
          </div>
        ))}
      </div>
    </section>
  );
}

export default SectionLocation;

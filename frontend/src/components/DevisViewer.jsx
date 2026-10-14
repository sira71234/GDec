import { useState } from "react";
import { genererDevis, telechargerDevis } from "../services/adminService";

function DevisViewer({ commande, devisInitial = null, onDevisGenere }) {
  const [devis, setDevis] = useState(devisInitial);
  const [chargement, setChargement] = useState(false);
  const [erreur, setErreur] = useState(null);

  const generer = async () => {
    setChargement(true);
    setErreur(null);
    try {
      const reponse = await genererDevis(commande.id);
      setDevis(reponse.devis);
      onDevisGenere?.(reponse.devis);
    } catch {
      setErreur("Génération du devis impossible.");
    } finally {
      setChargement(false);
    }
  };

  const telecharger = async () => {
    if (!devis) return;
    try {
      await telechargerDevis(devis.id, `devis_commande_${commande.id}.pdf`);
    } catch {
      setErreur("Téléchargement impossible.");
    }
  };

  return (
    <div className="p-3 bg-emerald-50 rounded-lg border border-emerald-100">
      <h4 className="font-semibold text-emerald-800 mb-2">Devis</h4>

      {devis ? (
        <div className="flex flex-wrap items-center gap-3 text-sm">
          <span className="text-gray-700">
            Montant :{" "}
            <strong>
              {Number(devis.montant_total).toLocaleString("fr-FR")} FCFA
            </strong>
          </span>
          <button
            type="button"
            onClick={telecharger}
            className="bg-emerald-700 hover:bg-emerald-800 text-white px-3 py-1.5 rounded-lg"
          >
            Télécharger le PDF
          </button>
        </div>
      ) : (
        <button
          type="button"
          onClick={generer}
          disabled={chargement}
          className="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-3 py-1.5 rounded-lg disabled:opacity-60"
        >
          {chargement ? "Génération…" : "Générer le devis"}
        </button>
      )}

      {erreur && <p className="text-red-600 text-sm mt-2">{erreur}</p>}
    </div>
  );
}

export default DevisViewer;

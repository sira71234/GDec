import { useEffect, useState } from "react";
import { genererDevis, telechargerDevis } from "../services/adminService";

function DevisViewer({ commande, devisInitial = null, onDevisGenere }) {
  const [devis, setDevis] = useState(devisInitial);
  const [chargement, setChargement] = useState(false);
  const [erreur, setErreur] = useState(null);

  // Le devis est un instantané : quand la commande change (montants, dates),
  // l'ancien PDF reste affiché jusqu'à une régénération explicite.
  useEffect(() => {
    setDevis(devisInitial);
  }, [devisInitial]);

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

      {devis && (
        <p className="text-gray-700 text-sm mb-2">
          Montant enregistré :{" "}
          <strong>
            {Number(devis.montant_total).toLocaleString("fr-FR")} FCFA
          </strong>
        </p>
      )}

      <div className="flex flex-wrap items-center gap-2 text-sm">
        <button
          type="button"
          onClick={generer}
          disabled={chargement}
          className="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-3 py-1.5 rounded-lg disabled:opacity-60"
        >
          {chargement
            ? "Génération…"
            : devis
              ? "Régénérer le devis"
              : "Générer le devis"}
        </button>
        {devis && (
          <button
            type="button"
            onClick={telecharger}
            className="bg-emerald-700 hover:bg-emerald-800 text-white px-3 py-1.5 rounded-lg"
          >
            Télécharger le PDF
          </button>
        )}
      </div>

      {devis && (
        <p className="text-xs text-gray-500 mt-2">
          Le montant et le PDF sont figés au moment de la génération. Régénérez
          après toute modification de la commande ou des tarifs.
        </p>
      )}

      {erreur && <p className="text-red-600 text-sm mt-2">{erreur}</p>}
    </div>
  );
}

export default DevisViewer;

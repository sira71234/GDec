import { useState } from "react";
import { getCommande } from "../services/adminService";
import DevisViewer from "./DevisViewer";

const LIBELLES_STATUT = {
  en_attente: "En attente",
  valide: "Validée",
  refuse: "Refusée",
};

const COULEURS_STATUT = {
  en_attente: "bg-yellow-100 text-yellow-800",
  valide: "bg-emerald-100 text-emerald-800",
  refuse: "bg-red-100 text-red-700",
};

const LIBELLES_TYPE = {
  location: "Location",
  decoration: "Décoration",
  les_deux: "Location + Décoration",
};

function ListeDemandes({ commandes, onChangerStatut, chargement }) {
  const [detail, setDetail] = useState(null);
  const [chargementDetail, setChargementDetail] = useState(false);

  const ouvrirDetail = async (id) => {
    setChargementDetail(true);
    try {
      setDetail(await getCommande(id));
    } finally {
      setChargementDetail(false);
    }
  };

  if (chargement) {
    return <p className="text-gray-500">Chargement des demandes…</p>;
  }

  if (!commandes || commandes.length === 0) {
    return <p className="text-gray-500">Aucune demande pour le moment.</p>;
  }

  return (
    <div>
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm border-collapse">
          <thead>
            <tr className="bg-emerald-700 text-white">
              <th className="py-2 px-3 border border-emerald-600">#</th>
              <th className="py-2 px-3 border border-emerald-600">Client</th>
              <th className="py-2 px-3 border border-emerald-600">Téléphone</th>
              <th className="py-2 px-3 border border-emerald-600">Type</th>
              <th className="py-2 px-3 border border-emerald-600">Caution</th>
              <th className="py-2 px-3 border border-emerald-600">Statut</th>
              <th className="py-2 px-3 border border-emerald-600 text-center">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            {commandes.map((commande, index) => (
              <tr
                key={commande.id}
                className={index % 2 === 0 ? "bg-gray-50" : "bg-white"}
              >
                <td className="py-2 px-3 border border-gray-200">{commande.id}</td>
                <td className="py-2 px-3 border border-gray-200">
                  {commande.client?.nom}
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {commande.client?.telephone}
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {LIBELLES_TYPE[commande.type_prestation] ?? commande.type_prestation}
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {Number(commande.montant_caution).toLocaleString("fr-FR")} FCFA
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  <span
                    className={`px-2 py-1 rounded-full text-xs font-semibold ${
                      COULEURS_STATUT[commande.statut]
                    }`}
                  >
                    {LIBELLES_STATUT[commande.statut] ?? commande.statut}
                  </span>
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  <div className="flex flex-wrap gap-1 justify-center">
                    <button
                      type="button"
                      onClick={() => ouvrirDetail(commande.id)}
                      className="text-emerald-700 hover:underline text-xs font-semibold"
                    >
                      Détails
                    </button>
                    {commande.statut !== "valide" && (
                      <button
                        type="button"
                        onClick={() => onChangerStatut(commande.id, "valide")}
                        className="text-emerald-700 hover:underline text-xs font-semibold"
                      >
                        Valider
                      </button>
                    )}
                    {commande.statut !== "refuse" && (
                      <button
                        type="button"
                        onClick={() => onChangerStatut(commande.id, "refuse")}
                        className="text-red-600 hover:underline text-xs font-semibold"
                      >
                        Refuser
                      </button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {(chargementDetail || detail) && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl max-w-2xl w-full max-h-[85vh] overflow-y-auto p-6">
            {chargementDetail ? (
              <p className="text-gray-500">Chargement du détail…</p>
            ) : (
              <>
                <div className="flex justify-between items-center mb-4">
                  <h3 className="text-lg font-bold text-emerald-800">
                    Demande n°{detail.id}
                  </h3>
                  <button
                    type="button"
                    onClick={() => setDetail(null)}
                    className="text-gray-500 hover:text-gray-800"
                  >
                    Fermer
                  </button>
                </div>

                <p className="text-sm text-gray-700 mb-1">
                  <strong>Client :</strong> {detail.client?.nom} —{" "}
                  {detail.client?.telephone}
                </p>
                {detail.client?.email && (
                  <p className="text-sm text-gray-700 mb-1">
                    <strong>Email :</strong> {detail.client?.email}
                  </p>
                )}
                {detail.client?.adresse && (
                  <p className="text-sm text-gray-700 mb-3">
                    <strong>Adresse :</strong> {detail.client?.adresse}
                  </p>
                )}

                {(detail.type_prestation === "location" ||
                  detail.type_prestation === "les_deux") && (
                  <div className="mb-3">
                    <h4 className="font-semibold text-emerald-800">Matériel loué</h4>
                    {(detail.materiels ?? []).length === 0 && (
                      <p className="text-sm text-gray-500">Aucun matériel.</p>
                    )}
                    <ul className="text-sm text-gray-700 list-disc list-inside">
                      {(detail.materiels ?? []).map((m) => (
                        <li key={m.id}>
                          {m.nom} × {m.pivot?.quantite}
                        </li>
                      ))}
                    </ul>
                    {detail.date_debut_location && (
                      <p className="text-sm text-gray-600 mt-1">
                        Du {detail.date_debut_location} au {detail.date_fin_location}
                      </p>
                    )}
                  </div>
                )}

                {(detail.type_prestation === "decoration" ||
                  detail.type_prestation === "les_deux") && (
                  <div className="mb-3">
                    <h4 className="font-semibold text-emerald-800">Décoration</h4>
                    <p className="text-sm text-gray-700">
                      Événement : {detail.type_evenement || "—"} | Thème :{" "}
                      {detail.theme || "—"} | Couleurs : {detail.couleurs || "—"}
                    </p>
                    <ul className="text-sm text-gray-700 list-disc list-inside">
                      {(detail.prestations_decoration ?? []).map((p) => (
                        <li key={p.id}>
                          {p.nom} × {p.pivot?.quantite}
                        </li>
                      ))}
                    </ul>
                    {(detail.elements_decor ?? []).length > 0 && (
                      <p className="text-sm text-gray-600 mt-1">
                        Éléments : {detail.elements_decor.map((e) => e.nom).join(", ")}
                      </p>
                    )}
                  </div>
                )}

                <DevisViewer
                  commande={detail}
                  devisInitial={(detail.devis ?? [])[0] ?? null}
                />
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

export default ListeDemandes;

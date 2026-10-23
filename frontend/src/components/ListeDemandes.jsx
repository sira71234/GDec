import { useState } from "react";
import { getCommande, updateCommande } from "../services/adminService";
import { couleurClient } from "../utils/couleurs";
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

const fcfa = (valeur) =>
  `${Number(valeur || 0).toLocaleString("fr-FR")} FCFA`;

const formatDateHeure = (valeur) =>
  valeur
    ? new Date(valeur).toLocaleString("fr-FR", {
        dateStyle: "short",
        timeStyle: "short",
      })
    : "";

const LIBELLES_ACTION = {
  creation: "Création",
  modification: "Modification",
  changement_statut: "Changement de statut",
  restauration: "Restauration",
  suppression_definitive: "Suppression définitive",
  devis: "Devis",
};

const versDateInput = (valeur) => (valeur ? String(valeur).slice(0, 10) : "");

const initialiserFormulaire = (commande) => ({
  statut: commande.statut ?? "en_attente",
  date_debut_location: versDateInput(commande.date_debut_location),
  date_fin_location: versDateInput(commande.date_fin_location),
  date_evenement: versDateInput(commande.date_evenement),
  duree_decoration: commande.duree_decoration ?? "",
  montant_decoration: commande.montant_decoration ?? "",
});

function ListeDemandes({
  commandes,
  onChangerStatut,
  onRafraichir,
  chargement,
  lectureSeule = false,
}) {
  const [detail, setDetail] = useState(null);
  const [chargementDetail, setChargementDetail] = useState(false);
  const [formulaire, setFormulaire] = useState(null);
  const [enregistrement, setEnregistrement] = useState(false);
  const [erreurEdition, setErreurEdition] = useState(null);
  const [messageEdition, setMessageEdition] = useState(null);

  const ouvrirDetail = async (id) => {
    setChargementDetail(true);
    setErreurEdition(null);
    setMessageEdition(null);
    try {
      const commande = await getCommande(id);
      setDetail(commande);
      setFormulaire(initialiserFormulaire(commande));
    } finally {
      setChargementDetail(false);
    }
  };

  const fermerDetail = () => {
    setDetail(null);
    setFormulaire(null);
    setErreurEdition(null);
    setMessageEdition(null);
  };

  const changerChamp = (champ, valeur) =>
    setFormulaire((actuel) => ({ ...actuel, [champ]: valeur }));

  const enregistrer = async () => {
    if (!detail || !formulaire) return;

    setEnregistrement(true);
    setErreurEdition(null);
    setMessageEdition(null);

    const estLocation =
      detail.type_prestation === "location" ||
      detail.type_prestation === "les_deux";
    const estDecoration =
      detail.type_prestation === "decoration" ||
      detail.type_prestation === "les_deux";

    const donnees = { statut: formulaire.statut };

    if (estLocation) {
      donnees.date_debut_location = formulaire.date_debut_location || null;
      donnees.date_fin_location = formulaire.date_fin_location || null;
    }

    if (estDecoration) {
      donnees.date_evenement = formulaire.date_evenement || null;
      donnees.duree_decoration =
        formulaire.duree_decoration === ""
          ? null
          : Number(formulaire.duree_decoration);
      donnees.montant_decoration =
        formulaire.montant_decoration === ""
          ? null
          : Number(formulaire.montant_decoration);
    }

    try {
      const reponse = await updateCommande(detail.id, donnees);
      const commande = reponse.commande;
      setDetail(commande);
      setFormulaire(initialiserFormulaire(commande));
      setMessageEdition("Commande mise à jour.");
      onRafraichir?.();
    } catch (error) {
      const details = error.response?.data?.errors;
      const premierMessage = details
        ? Object.values(details).flat()[0]
        : "Enregistrement impossible. Veuillez réessayer.";
      setErreurEdition(premierMessage);
    } finally {
      setEnregistrement(false);
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
              <th className="py-2 px-3 border border-emerald-600">Dernière action</th>
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
                  <span className="flex items-center gap-2">
                    <span
                      className={`w-2.5 h-2.5 rounded-full ${
                        couleurClient(commande.client_id).pastille
                      }`}
                    />
                    {commande.client?.nom}
                  </span>
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {commande.client?.telephone}
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {LIBELLES_TYPE[commande.type_prestation] ?? commande.type_prestation}
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {commande.derniere_action ? (
                    <>
                      <div className="text-xs">
                        {commande.derniere_action.description}
                      </div>
                      <div className="text-[11px] text-gray-500">
                        {formatDateHeure(commande.derniere_action.created_at)}
                        {commande.derniere_action.admin?.identifiant
                          ? ` — ${commande.derniere_action.admin.identifiant}`
                          : ""}
                      </div>
                    </>
                  ) : (
                    <span className="text-gray-400">—</span>
                  )}
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {fcfa(commande.montant_caution)}
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
                    {!lectureSeule && commande.statut !== "valide" && (
                      <button
                        type="button"
                        onClick={() => onChangerStatut(commande.id, "valide")}
                        className="text-emerald-700 hover:underline text-xs font-semibold"
                      >
                        Valider
                      </button>
                    )}
                    {!lectureSeule && commande.statut !== "refuse" && (
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
          <div className="bg-white rounded-xl max-w-3xl w-full max-h-[85vh] overflow-y-auto p-6">
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
                    onClick={fermerDetail}
                    className="text-gray-500 hover:text-gray-800"
                  >
                    Fermer
                  </button>
                </div>

                <p className="text-sm text-gray-700 mb-1 flex items-center gap-2">
                  <span
                    className={`w-3 h-3 rounded-full ${
                      couleurClient(detail.client_id).pastille
                    }`}
                  />
                  <span>
                    <strong>Client :</strong> {detail.client?.nom} —{" "}
                    {detail.client?.telephone}
                  </span>
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
                  <div className="mb-4">
                    <h4 className="font-semibold text-emerald-800">Matériel loué</h4>
                    {(detail.materiels ?? []).length === 0 && (
                      <p className="text-sm text-gray-500">Aucun matériel.</p>
                    )}
                    <ul className="text-sm text-gray-700 list-disc list-inside">
                      {(detail.materiels ?? []).map((m) => (
                        <li key={m.id}>
                          {m.nom} × {m.pivot?.quantite} —{" "}
                          {fcfa(
                            Number(m.prix_unitaire) * Number(m.pivot?.quantite),
                          )}{" "}
                          / jour
                        </li>
                      ))}
                    </ul>
                    {detail.date_debut_location && (
                      <p className="text-sm text-gray-600 mt-1">
                        Du {versDateInput(detail.date_debut_location)} au{" "}
                        {versDateInput(detail.date_fin_location)}
                      </p>
                    )}
                    <p className="text-sm text-gray-700 mt-1">
                      <strong>Sous-total location :</strong>{" "}
                      {fcfa(detail.montant_location)}
                    </p>
                  </div>
                )}

                {(detail.type_prestation === "decoration" ||
                  detail.type_prestation === "les_deux") && (
                  <div className="mb-4">
                    <h4 className="font-semibold text-emerald-800">Décoration</h4>
                    <p className="text-sm text-gray-700">
                      Événement : {detail.type_evenement || "—"}
                      {detail.date_evenement && (
                        <> — Date : {versDateInput(detail.date_evenement)}</>
                      )}
                      {detail.duree_decoration && (
                        <> — Durée : {detail.duree_decoration} jour(s)</>
                      )}
                    </p>
                    <p className="text-sm text-gray-700">
                      Thème : {detail.theme || "—"} — Couleurs :{" "}
                      {detail.couleurs || "—"} — Invités :{" "}
                      {detail.nombre_personnes || "—"}
                    </p>
                    <ul className="text-sm text-gray-700 list-disc list-inside">
                      {(detail.prestations_decoration ?? []).map((p) => (
                        <li key={p.id}>
                          {p.nom} × {p.pivot?.quantite} —{" "}
                          {fcfa(Number(p.prix) * Number(p.pivot?.quantite ?? 1))}
                        </li>
                      ))}
                    </ul>
                    {(detail.elements_decor ?? []).length > 0 && (
                      <p className="text-sm text-gray-600 mt-1">
                        Éléments : {detail.elements_decor.map((e) => e.nom).join(", ")}
                      </p>
                    )}
                    <p className="text-sm text-gray-700 mt-1">
                      <strong>Décoration calculée :</strong>{" "}
                      {fcfa(detail.montant_decoration_calcule)}
                      {detail.montant_decoration !== null && (
                        <>
                          {" "}
                          — <strong>Montant retenu :</strong>{" "}
                          {fcfa(detail.montant_decoration_effectif)}
                        </>
                      )}
                    </p>
                  </div>
                )}

                <div className="mb-4 bg-emerald-50 border border-emerald-100 rounded-lg p-3 text-sm text-gray-800">
                  <p>
                    <strong>Total général :</strong> {fcfa(detail.montant_total)}
                  </p>
                  <p>
                    <strong>Caution :</strong> {fcfa(detail.montant_caution)}
                  </p>
                </div>

                {!lectureSeule && formulaire && (
                  <div className="mb-4 border border-gray-200 rounded-lg p-3">
                    <h4 className="font-semibold text-emerald-800 mb-2">
                      Modifier la commande
                    </h4>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                      <div>
                        <label className="block font-semibold text-gray-700 mb-1">
                          Statut
                        </label>
                        <select
                          value={formulaire.statut}
                          onChange={(e) =>
                            changerChamp("statut", e.target.value)
                          }
                          className="w-full border border-gray-300 rounded-lg px-2 py-1.5"
                        >
                          <option value="en_attente">En attente</option>
                          <option value="valide">Validée</option>
                          <option value="refuse">Refusée</option>
                        </select>
                      </div>

                      {(detail.type_prestation === "location" ||
                        detail.type_prestation === "les_deux") && (
                        <>
                          <div>
                            <label className="block font-semibold text-gray-700 mb-1">
                              Début de location
                            </label>
                            <input
                              type="date"
                              value={formulaire.date_debut_location}
                              onChange={(e) =>
                                changerChamp("date_debut_location", e.target.value)
                              }
                              className="w-full border border-gray-300 rounded-lg px-2 py-1.5"
                            />
                          </div>
                          <div>
                            <label className="block font-semibold text-gray-700 mb-1">
                              Fin de location
                            </label>
                            <input
                              type="date"
                              value={formulaire.date_fin_location}
                              onChange={(e) =>
                                changerChamp("date_fin_location", e.target.value)
                              }
                              className="w-full border border-gray-300 rounded-lg px-2 py-1.5"
                            />
                          </div>
                        </>
                      )}

                      {(detail.type_prestation === "decoration" ||
                        detail.type_prestation === "les_deux") && (
                        <>
                          <div>
                            <label className="block font-semibold text-gray-700 mb-1">
                              Date de la décoration
                            </label>
                            <input
                              type="date"
                              value={formulaire.date_evenement}
                              onChange={(e) =>
                                changerChamp("date_evenement", e.target.value)
                              }
                              className="w-full border border-gray-300 rounded-lg px-2 py-1.5"
                            />
                          </div>
                          <div>
                            <label className="block font-semibold text-gray-700 mb-1">
                              Durée (jours)
                            </label>
                            <input
                              type="number"
                              min="1"
                              value={formulaire.duree_decoration}
                              onChange={(e) =>
                                changerChamp("duree_decoration", e.target.value)
                              }
                              className="w-full border border-gray-300 rounded-lg px-2 py-1.5"
                            />
                          </div>
                          <div>
                            <label className="block font-semibold text-gray-700 mb-1">
                              Montant décoration (FCFA)
                            </label>
                            <input
                              type="number"
                              min="0"
                              step="1"
                              placeholder="Laisser vide = calcul automatique"
                              value={formulaire.montant_decoration}
                              onChange={(e) =>
                                changerChamp("montant_decoration", e.target.value)
                              }
                              className="w-full border border-gray-300 rounded-lg px-2 py-1.5"
                            />
                          </div>
                        </>
                      )}
                    </div>

                    <div className="flex flex-wrap items-center gap-3 mt-3">
                      <button
                        type="button"
                        onClick={enregistrer}
                        disabled={enregistrement}
                        className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-1.5 rounded-lg disabled:opacity-60"
                      >
                        {enregistrement ? "Enregistrement…" : "Enregistrer"}
                      </button>
                      {messageEdition && (
                        <span className="text-emerald-700 text-sm">
                          {messageEdition}
                        </span>
                      )}
                      {erreurEdition && (
                        <span className="text-red-600 text-sm">{erreurEdition}</span>
                      )}
                    </div>
                  </div>
                )}

                <div className="mb-4 border border-gray-200 rounded-lg p-3">
                  <h4 className="font-semibold text-emerald-800 mb-2">
                    Historique des actions
                  </h4>
                  {(detail.historiques ?? []).length === 0 ? (
                    <p className="text-sm text-gray-500">
                      Aucune action enregistrée pour cette commande.
                    </p>
                  ) : (
                    <ol className="space-y-2">
                      {(detail.historiques ?? []).map((action) => (
                        <li key={action.id} className="flex gap-2 text-sm">
                          <span className="mt-1.5 w-2 h-2 rounded-full bg-emerald-600 shrink-0" />
                          <div>
                            <p className="text-gray-800">
                              <strong>
                                {LIBELLES_ACTION[action.action] ?? action.action}
                              </strong>{" "}
                              — {action.description}
                            </p>
                            <p className="text-[11px] text-gray-500">
                              {formatDateHeure(action.created_at)}
                              {action.admin?.identifiant
                                ? ` — par ${action.admin.identifiant}`
                                : " — par le client"}
                            </p>
                          </div>
                        </li>
                      ))}
                    </ol>
                  )}
                </div>

                <DevisViewer
                  commande={detail}
                  devisInitial={(detail.devis ?? [])[0] ?? null}
                  onDevisGenere={() => ouvrirDetail(detail.id)}
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

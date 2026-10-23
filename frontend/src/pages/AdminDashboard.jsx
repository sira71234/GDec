import { useEffect, useState, useCallback } from "react";
import { useNavigate } from "react-router-dom";
import ListeDemandes from "../components/ListeDemandes";
import CalendrierDisponibilite from "../components/CalendrierDisponibilite";
import ListeDisponibilite from "../components/ListeDisponibilite";
import AdminCatalogue from "../components/AdminCatalogue";
import AdminVitrine from "../components/AdminVitrine";
import {
  isAuthenticated,
  getAdmin,
  logout,
  getCommandes,
  getHistoriqueCommandes,
  updateStatut,
  getCorbeille,
  restaurerCommande,
  supprimerDefinitivement,
} from "../services/adminService";

const FILTRES = [
  { valeur: "", libelle: "Toutes" },
  { valeur: "en_attente", libelle: "En attente" },
  { valeur: "valide", libelle: "Validées" },
  { valeur: "refuse", libelle: "Refusées" },
];

function AdminDashboard() {
  const navigate = useNavigate();
  const [onglet, setOnglet] = useState("demandes");
  const [filtre, setFiltre] = useState("");
  const [commandes, setCommandes] = useState([]);
  const [historiqueCommandes, setHistoriqueCommandes] = useState([]);
  const [corbeille, setCorbeille] = useState([]);
  const [chargement, setChargement] = useState(true);
  const [erreur, setErreur] = useState(null);

  const admin = getAdmin();

  useEffect(() => {
    if (!isAuthenticated()) {
      navigate("/admin/login", { replace: true });
    }
  }, [navigate]);

  const chargerCommandes = useCallback(async () => {
    setChargement(true);
    setErreur(null);
    try {
      const reponse = await getCommandes(filtre ? { statut: filtre } : {});
      setCommandes(reponse.data ?? []);
    } catch {
      setErreur("Impossible de charger les demandes.");
    } finally {
      setChargement(false);
    }
  }, [filtre]);

  const chargerCorbeille = useCallback(async () => {
    setChargement(true);
    try {
      const reponse = await getCorbeille();
      setCorbeille(reponse.data ?? []);
    } catch {
      setErreur("Impossible de charger la corbeille.");
    } finally {
      setChargement(false);
    }
  }, []);

  const chargerHistorique = useCallback(async () => {
    setChargement(true);
    setErreur(null);
    try {
      const reponse = await getHistoriqueCommandes();
      setHistoriqueCommandes(reponse.data ?? []);
    } catch {
      setErreur("Impossible de charger l'historique.");
    } finally {
      setChargement(false);
    }
  }, []);

  useEffect(() => {
    if (onglet === "demandes") {
      chargerCommandes();
    } else if (onglet === "corbeille") {
      chargerCorbeille();
    } else if (onglet === "historique") {
      chargerHistorique();
    }
  }, [onglet, chargerCommandes, chargerCorbeille, chargerHistorique]);

  const changerStatut = async (id, statut) => {
    try {
      await updateStatut(id, statut);
      await chargerCommandes();
    } catch {
      setErreur("Mise à jour du statut impossible.");
    }
  };

  const deconnecter = async () => {
    await logout();
    navigate("/admin/login", { replace: true });
  };

  const statistiques = {
    total: commandes.length,
    enAttente: commandes.filter((c) => c.statut === "en_attente").length,
    valides: commandes.filter((c) => c.statut === "valide").length,
    refuses: commandes.filter((c) => c.statut === "refuse").length,
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-emerald-800 text-white">
        <div className="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
          <div>
            <h1 className="text-xl font-bold">Tableau de bord administrateur</h1>
            {admin && (
              <p className="text-emerald-200 text-sm">
                Connecté : {admin.identifiant}
              </p>
            )}
          </div>
          <button
            type="button"
            onClick={deconnecter}
            className="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-4 py-2 rounded-lg"
          >
            Déconnexion
          </button>
        </div>
      </header>

      <main className="max-w-6xl mx-auto px-4 py-6">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          {[
            { libelle: "Total", valeur: statistiques.total, couleur: "text-gray-700" },
            { libelle: "En attente", valeur: statistiques.enAttente, couleur: "text-yellow-600" },
            { libelle: "Validées", valeur: statistiques.valides, couleur: "text-emerald-700" },
            { libelle: "Refusées", valeur: statistiques.refuses, couleur: "text-red-600" },
          ].map((carte) => (
            <div
              key={carte.libelle}
              className="bg-white rounded-xl shadow p-4 text-center"
            >
              <p className="text-sm text-gray-500">{carte.libelle}</p>
              <p className={`text-2xl font-bold ${carte.couleur}`}>
                {carte.valeur}
              </p>
            </div>
          ))}
        </div>

        <div className="flex gap-2 mb-6">
          {[
            { cle: "demandes", libelle: "Demandes" },
            { cle: "disponibilites", libelle: "Disponibilités" },
            { cle: "historique", libelle: "Historique" },
            { cle: "catalogue", libelle: "Catalogue" },
            { cle: "vitrine", libelle: "Vitrine" },
            { cle: "corbeille", libelle: "Corbeille" },
          ].map((item) => (
            <button
              key={item.cle}
              type="button"
              onClick={() => setOnglet(item.cle)}
              className={`px-4 py-2 rounded-lg font-semibold transition ${
                onglet === item.cle
                  ? "bg-emerald-700 text-white"
                  : "bg-white text-gray-700 border border-gray-200 hover:border-emerald-600"
              }`}
            >
              {item.libelle}
            </button>
          ))}
        </div>

        {erreur && <p className="text-red-600 mb-4">{erreur}</p>}

        <div className="bg-white rounded-xl shadow p-6">
          {onglet === "demandes" && (
            <>
              <div className="flex flex-wrap gap-2 mb-4">
                {FILTRES.map((f) => (
                  <button
                    key={f.valeur}
                    type="button"
                    onClick={() => setFiltre(f.valeur)}
                    className={`px-3 py-1 rounded-full text-sm font-semibold ${
                      filtre === f.valeur
                        ? "bg-emerald-100 text-emerald-800"
                        : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                    }`}
                  >
                    {f.libelle}
                  </button>
                ))}
              </div>
              <ListeDemandes
                commandes={commandes}
                onChangerStatut={changerStatut}
                onRafraichir={chargerCommandes}
                chargement={chargement}
              />
            </>
          )}

          {onglet === "disponibilites" && (
            <div className="space-y-8">
              <CalendrierDisponibilite />
              <div>
                <h3 className="font-bold text-emerald-800 mb-3">
                  Réservations
                </h3>
                <ListeDisponibilite />
              </div>
            </div>
          )}

          {onglet === "historique" && (
            <>
              <p className="text-sm text-gray-600 mb-4">
                Les commandes dont la date est dépassée de plus de deux jours
                sont retirées des demandes actives et conservées ici. Cliquez sur
                « Détails » pour consulter la commande et son journal d'actions.
              </p>
              <ListeDemandes
                commandes={historiqueCommandes}
                onChangerStatut={changerStatut}
                onRafraichir={chargerHistorique}
                chargement={chargement}
                lectureSeule
              />
            </>
          )}

          {onglet === "catalogue" && <AdminCatalogue />}

          {onglet === "vitrine" && <AdminVitrine />}

          {onglet === "corbeille" && (
            <div className="overflow-x-auto">
              {chargement ? (
                <p className="text-gray-500">Chargement…</p>
              ) : corbeille.length === 0 ? (
                <p className="text-gray-500">La corbeille est vide.</p>
              ) : (
                <table className="w-full text-left text-sm border-collapse">
                  <thead>
                    <tr className="bg-gray-700 text-white">
                      <th className="py-2 px-3 border border-gray-600">#</th>
                      <th className="py-2 px-3 border border-gray-600">Client</th>
                      <th className="py-2 px-3 border border-gray-600">Type</th>
                      <th className="py-2 px-3 border border-gray-600 text-center">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {corbeille.map((commande, index) => (
                      <tr
                        key={commande.id}
                        className={index % 2 === 0 ? "bg-gray-50" : "bg-white"}
                      >
                        <td className="py-2 px-3 border border-gray-200">
                          {commande.id}
                        </td>
                        <td className="py-2 px-3 border border-gray-200">
                          {commande.client?.nom}
                        </td>
                        <td className="py-2 px-3 border border-gray-200">
                          {commande.type_prestation}
                        </td>
                        <td className="py-2 px-3 border border-gray-200 text-center">
                          <div className="flex gap-2 justify-center">
                            <button
                              type="button"
                              onClick={async () => {
                                await restaurerCommande(commande.id);
                                chargerCorbeille();
                              }}
                              className="text-emerald-700 hover:underline text-xs font-semibold"
                            >
                              Restaurer
                            </button>
                            <button
                              type="button"
                              onClick={async () => {
                                await supprimerDefinitivement(commande.id);
                                chargerCorbeille();
                              }}
                              className="text-red-600 hover:underline text-xs font-semibold"
                            >
                              Supprimer
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          )}
        </div>
      </main>
    </div>
  );
}

export default AdminDashboard;

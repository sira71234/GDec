import { useCallback, useEffect, useState } from "react";
import { Link, Navigate, useNavigate } from "react-router-dom";
import { getAdmin, isAuthenticated, logout } from "../services/adminService";
import {
  creerAdministrateur,
  getAdministrateurs,
  modifierAdministrateur,
  supprimerAdministrateur,
} from "../services/superAdminService";

const ROLES = [
  { valeur: "admin", libelle: "Administrateur" },
  { valeur: "super_admin", libelle: "Super administrateur" },
];

function libelleRole(role) {
  return role === "super_admin" ? "Super administrateur" : "Administrateur";
}

// Premier message de validation renvoyé par l'API, sinon un texte générique.
function messageErreur(error, defaut) {
  const erreurs = error?.response?.data?.errors;
  if (erreurs && typeof erreurs === "object") {
    const premier = Object.values(erreurs)[0];
    if (Array.isArray(premier) && premier.length > 0) {
      return premier[0];
    }
  }

  if (error?.response?.status === 403) {
    return "Accès réservé au super administrateur.";
  }
  if (error?.response?.status === 401) {
    return "Session expirée. Reconnectez-vous.";
  }
  if (!error?.response) {
    return "Serveur injoignable. Vérifiez votre connexion internet.";
  }

  return error?.response?.data?.message || defaut;
}

function SuperAdminGestion() {
  const navigate = useNavigate();
  const admin = getAdmin();

  const [comptes, setComptes] = useState([]);
  const [chargement, setChargement] = useState(true);
  const [erreur, setErreur] = useState(null);
  const [message, setMessage] = useState(null);
  const [envoi, setEnvoi] = useState(false);
  const [formulaire, setFormulaire] = useState({
    identifiant: "",
    mot_de_passe: "",
    code_admin: "",
    role: "admin",
  });

  const autorise = isAuthenticated() && admin?.role === "super_admin";

  const charger = useCallback(async () => {
    setChargement(true);
    setErreur(null);
    try {
      setComptes(await getAdministrateurs());
    } catch (error) {
      setErreur(messageErreur(error, "Impossible de charger les comptes."));
    } finally {
      setChargement(false);
    }
  }, []);

  useEffect(() => {
    if (!autorise) {
      return;
    }
    charger();
  }, [autorise, charger]);

  if (!autorise) {
    return <Navigate to="/super-admin" replace />;
  }

  const creer = async (event) => {
    event.preventDefault();
    setErreur(null);
    setMessage(null);
    setEnvoi(true);

    try {
      await creerAdministrateur(formulaire);
      setFormulaire({
        identifiant: "",
        mot_de_passe: "",
        code_admin: "",
        role: "admin",
      });
      setMessage(
        `Compte « ${formulaire.identifiant} » créé (${libelleRole(formulaire.role)}).`,
      );
      await charger();
    } catch (error) {
      setErreur(messageErreur(error, "Création du compte impossible."));
    } finally {
      setEnvoi(false);
    }
  };

  const changerRole = async (compte, role) => {
    setErreur(null);
    setMessage(null);

    try {
      await modifierAdministrateur(compte.id, {
        identifiant: compte.identifiant,
        role,
      });
      setMessage(`Rôle de « ${compte.identifiant} » mis à jour.`);
      await charger();
    } catch (error) {
      setErreur(messageErreur(error, "Modification du rôle impossible."));
    }
  };

  const supprimer = async (compte) => {
    if (
      !window.confirm(
        `Supprimer définitivement le compte « ${compte.identifiant} » ?`,
      )
    ) {
      return;
    }

    setErreur(null);
    setMessage(null);

    try {
      await supprimerAdministrateur(compte.id);
      setMessage(`Compte « ${compte.identifiant} » supprimé.`);
      await charger();
    } catch (error) {
      setErreur(messageErreur(error, "Suppression du compte impossible."));
    }
  };

  const deconnecter = async () => {
    await logout();
    navigate("/super-admin", { replace: true });
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-emerald-950 text-white">
        <div className="max-w-5xl mx-auto px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-xl font-bold">Super administrateur</h1>
            <p className="text-emerald-200 text-sm">
              Connecté : {admin?.identifiant}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Link
              to="/admin/dashboard"
              className="bg-emerald-700 hover:bg-emerald-600 text-white font-semibold px-4 py-2 rounded-lg"
            >
              Espace administrateur
            </Link>
            <button
              type="button"
              onClick={deconnecter}
              className="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-4 py-2 rounded-lg"
            >
              Déconnexion
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-5xl mx-auto px-4 py-6 space-y-6">
        {erreur && <p className="text-red-600">{erreur}</p>}
        {message && <p className="text-emerald-700">{message}</p>}

        <section className="bg-white rounded-xl shadow p-6">
          <h2 className="text-lg font-bold text-emerald-800 mb-4">
            Créer un compte
          </h2>

          <form onSubmit={creer} className="grid gap-4 md:grid-cols-2">
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Identifiant
              </label>
              <input
                type="text"
                value={formulaire.identifiant}
                onChange={(e) =>
                  setFormulaire({ ...formulaire, identifiant: e.target.value })
                }
                autoComplete="off"
                required
                minLength={3}
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Rôle
              </label>
              <select
                value={formulaire.role}
                onChange={(e) =>
                  setFormulaire({ ...formulaire, role: e.target.value })
                }
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
              >
                {ROLES.map((role) => (
                  <option key={role.valeur} value={role.valeur}>
                    {role.libelle}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Mot de passe
              </label>
              <input
                type="password"
                value={formulaire.mot_de_passe}
                onChange={(e) =>
                  setFormulaire({ ...formulaire, mot_de_passe: e.target.value })
                }
                autoComplete="new-password"
                required
                minLength={8}
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
              />
              <p className="text-xs text-gray-500 mt-1">8 caractères minimum.</p>
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Code administrateur
              </label>
              <input
                type="password"
                value={formulaire.code_admin}
                onChange={(e) =>
                  setFormulaire({ ...formulaire, code_admin: e.target.value })
                }
                inputMode="numeric"
                autoComplete="off"
                required
                pattern="[0-9]{6}"
                maxLength={6}
                title="6 chiffres"
                className="w-full border border-gray-300 rounded-lg px-3 py-2 tracking-widest text-center"
              />
              <p className="text-xs text-gray-500 mt-1">
                Exactement 6 chiffres, demandés à chaque connexion.
              </p>
            </div>

            <div className="md:col-span-2">
              <button
                type="submit"
                disabled={envoi}
                className="bg-emerald-800 hover:bg-emerald-900 text-white font-semibold px-5 py-2 rounded-lg disabled:opacity-60"
              >
                {envoi ? "Création…" : "Créer le compte"}
              </button>
            </div>
          </form>
        </section>

        <section className="bg-white rounded-xl shadow p-6">
          <h2 className="text-lg font-bold text-emerald-800 mb-4">
            Comptes existants
          </h2>

          {chargement ? (
            <p className="text-gray-500">Chargement…</p>
          ) : comptes.length === 0 ? (
            <p className="text-gray-500">Aucun compte.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left text-gray-500 border-b">
                    <th className="py-2 pr-4">Identifiant</th>
                    <th className="py-2 pr-4">Rôle</th>
                    <th className="py-2 pr-4">Créé le</th>
                    <th className="py-2">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {comptes.map((compte) => {
                    const estMoi = compte.id === admin?.id;

                    return (
                      <tr key={compte.id} className="border-b last:border-0">
                        <td className="py-2 pr-4 font-semibold text-gray-800">
                          {compte.identifiant}
                          {estMoi && (
                            <span className="ml-2 text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded">
                              vous
                            </span>
                          )}
                        </td>
                        <td className="py-2 pr-4">
                          <select
                            value={compte.role}
                            onChange={(e) =>
                              changerRole(compte, e.target.value)
                            }
                            className={`border rounded-lg px-2 py-1 ${
                              compte.role === "super_admin"
                                ? "border-emerald-600 text-emerald-800"
                                : "border-gray-300 text-gray-700"
                            }`}
                          >
                            {ROLES.map((role) => (
                              <option key={role.valeur} value={role.valeur}>
                                {role.libelle}
                              </option>
                            ))}
                          </select>
                        </td>
                        <td className="py-2 pr-4 text-gray-600">
                          {compte.created_at
                            ? new Date(compte.created_at).toLocaleDateString(
                                "fr-FR",
                              )
                            : "—"}
                        </td>
                        <td className="py-2">
                          <button
                            type="button"
                            onClick={() => supprimer(compte)}
                            disabled={estMoi}
                            title={
                              estMoi
                                ? "Vous ne pouvez pas supprimer votre propre compte."
                                : "Supprimer ce compte"
                            }
                            className="text-red-600 font-semibold hover:underline disabled:text-gray-400 disabled:no-underline"
                          >
                            Supprimer
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}

          <p className="text-xs text-gray-500 mt-4">
            Un administrateur ne peut pas accéder à cet espace, mais un super
            administrateur peut se connecter aussi bien ici que sur l'espace
            administrateur classique. Le dernier super administrateur ne peut
            être ni supprimé ni rétrogradé.
          </p>
        </section>
      </main>
    </div>
  );
}

export default SuperAdminGestion;

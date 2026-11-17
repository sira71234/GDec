import api, { asArray, asObject } from "./api";
import { enregistrerSession } from "./authStorage";

// --- Authentification super administrateur ---

export async function loginSuperAdmin(identifiant, motDePasse, codeAdmin) {
  const reponse = await api.post("/super-admin/login", {
    identifiant,
    mot_de_passe: motDePasse,
    code_admin: codeAdmin,
  });

  const data = asObject(reponse.data);
  const jeton = typeof data.token === "string" ? data.token.trim() : "";

  // Même garde que la connexion admin : sans jeton, on ne navigue pas.
  if (!jeton) {
    const erreur = new Error(
      `Réponse de connexion invalide (HTTP ${reponse.status}).`,
    );
    erreur.code = "REPONSE_INVALIDE";
    erreur.statut = reponse.status;
    throw erreur;
  }

  const admin = asObject(data.admin);
  enregistrerSession(jeton, Object.keys(admin).length > 0 ? admin : null);

  return { ...data, token: jeton };
}

// --- Gestion des comptes administrateurs ---

export async function getAdministrateurs() {
  const { data } = await api.get("/super-admin/administrateurs");
  return asArray(data?.administrateurs);
}

export async function creerAdministrateur(donnees) {
  const { data } = await api.post("/super-admin/administrateurs", donnees);
  return asObject(data);
}

export async function modifierAdministrateur(id, donnees) {
  const { data } = await api.put(`/super-admin/administrateurs/${id}`, donnees);
  return asObject(data);
}

export async function supprimerAdministrateur(id) {
  const { data } = await api.delete(`/super-admin/administrateurs/${id}`);
  return asObject(data);
}

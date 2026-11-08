import api, { asArray, asObject } from "./api";
import {
  effacerSession,
  enregistrerSession,
  lireAdmin,
  lireJeton,
} from "./authStorage";

// --- Authentification administrateur ---

export async function login(identifiant, motDePasse, codeAdmin) {
  const reponse = await api.post("/admin/login", {
    identifiant,
    mot_de_passe: motDePasse,
    code_admin: codeAdmin,
  });

  const data = asObject(reponse.data);
  const jeton = typeof data.token === "string" ? data.token.trim() : "";

  // Un serveur mal configuré peut répondre 200 avec une page HTML (proxy,
  // page de suspension, rewrite cassé). Sans jeton, on refuse la connexion
  // au lieu de naviguer vers le tableau de bord pour revenir aussitôt.
  if (!jeton) {
    const typeContenu = reponse.headers?.["content-type"] ?? "inconnu";
    const erreur = new Error(
      `Réponse de connexion invalide (HTTP ${reponse.status}, ${typeContenu}). ` +
        "Le serveur n'a pas renvoyé de jeton.",
    );
    erreur.code = "REPONSE_INVALIDE";
    erreur.statut = reponse.status;
    erreur.typeContenu = typeContenu;
    throw erreur;
  }

  const admin = asObject(data.admin);
  enregistrerSession(jeton, Object.keys(admin).length > 0 ? admin : null);

  return { ...data, token: jeton };
}

export async function logout() {
  try {
    await api.post("/admin/logout");
  } finally {
    effacerSession();
  }
}

export async function me() {
  const { data } = await api.get("/admin/me");
  return asObject(data);
}

export function getAdmin() {
  return lireAdmin();
}

export function isAuthenticated() {
  return Boolean(lireJeton());
}

// --- Demandes ---

export async function getCommandes(params = {}) {
  const { data } = await api.get("/admin/commandes", { params });
  return { ...asObject(data), data: asArray(data?.data ?? data) };
}

export async function getCommande(id) {
  const { data } = await api.get(`/admin/commandes/${id}`);
  return data;
}

// Commandes passées (date dépassée de plus de deux jours).
export async function getHistoriqueCommandes(params = {}) {
  const { data } = await api.get("/admin/commandes/historique", { params });
  return { ...asObject(data), data: asArray(data?.data ?? data) };
}

export async function updateStatut(id, statut) {
  const { data } = await api.patch(`/admin/commandes/${id}/statut`, { statut });
  return data;
}

// Modifie une commande (montant décoration, dates, statut) avant le devis.
export async function updateCommande(id, donnees) {
  const { data } = await api.patch(`/admin/commandes/${id}`, donnees);
  return data;
}

export async function getCorbeille() {
  const { data } = await api.get("/admin/commandes/corbeille");
  return { ...asObject(data), data: asArray(data?.data ?? data) };
}

export async function restaurerCommande(id) {
  const { data } = await api.patch(`/admin/commandes/${id}/restaurer`);
  return data;
}

export async function supprimerDefinitivement(id) {
  const { data } = await api.delete(`/admin/commandes/${id}/definitif`);
  return data;
}

// --- Devis ---

export async function genererDevis(commandeId) {
  const { data } = await api.post(`/admin/commandes/${commandeId}/devis`);
  return data;
}

// Le devis est protégé : on le récupère en blob (avec le jeton) puis on force le téléchargement.
export async function telechargerDevis(devisId, nomFichier = "devis.pdf") {
  const response = await api.get(`/devis/${devisId}/telecharger`, {
    responseType: "blob",
  });
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const lien = document.createElement("a");
  lien.href = url;
  lien.setAttribute("download", nomFichier);
  document.body.appendChild(lien);
  lien.click();
  lien.remove();
  window.URL.revokeObjectURL(url);
}

// La pièce d'identité est également stockée sur un disque privé.
export async function telechargerPieceIdentite(commandeId) {
  const response = await api.get(`/admin/commandes/${commandeId}/piece-identite`, {
    responseType: "blob",
  });
  const url = window.URL.createObjectURL(
    new Blob([response.data], { type: response.headers["content-type"] }),
  );
  const lien = document.createElement("a");
  lien.href = url;
  lien.setAttribute("download", `piece_identite_commande_${commandeId}`);
  document.body.appendChild(lien);
  lien.click();
  lien.remove();
  window.URL.revokeObjectURL(url);
}

// --- Disponibilités ---

export async function getDisponibilites(params = {}) {
  const { data } = await api.get("/admin/disponibilites", { params });
  return asArray(data);
}

// --- Gestion du catalogue (matériels / prestations / éléments) ---
// Les mises à jour sont envoyées en POST multipart (upload de photo) : les routes
// Laravel acceptent POST en plus de PUT/PATCH sur les endpoints de mise à jour.

export async function getAdminMateriels() {
  const { data } = await api.get("/admin/materiels");
  return asArray(data);
}

export async function createMateriel(formData) {
  const { data } = await api.post("/admin/materiels", formData);
  return data;
}

export async function updateMateriel(id, formData) {
  const { data } = await api.post(`/admin/materiels/${id}`, formData);
  return data;
}

export async function deleteMateriel(id) {
  const { data } = await api.delete(`/admin/materiels/${id}`);
  return data;
}

export async function getAdminPrestations() {
  const { data } = await api.get("/admin/prestations-decoration");
  return asArray(data);
}

export async function createPrestation(formData) {
  const { data } = await api.post("/admin/prestations-decoration", formData);
  return data;
}

export async function updatePrestation(id, formData) {
  const { data } = await api.post(`/admin/prestations-decoration/${id}`, formData);
  return data;
}

export async function deletePrestation(id) {
  const { data } = await api.delete(`/admin/prestations-decoration/${id}`);
  return data;
}

export async function getAdminElements() {
  const { data } = await api.get("/admin/elements-decor");
  return asArray(data);
}

export async function createElement(formData) {
  const { data } = await api.post("/admin/elements-decor", formData);
  return data;
}

export async function updateElement(id, formData) {
  const { data } = await api.post(`/admin/elements-decor/${id}`, formData);
  return data;
}

export async function deleteElement(id) {
  const { data } = await api.delete(`/admin/elements-decor/${id}`);
  return data;
}

// --- Gestion de la vitrine (textes et images de la page d'accueil) ---

export async function getAdminVitrine() {
  const { data } = await api.get("/admin/vitrine");
  return {
    ...asObject(data),
    banniere: asObject(data?.banniere),
    galerie: asArray(data?.galerie),
    sections: asArray(data?.sections),
  };
}

export async function updateBanniere(formData) {
  const { data } = await api.post("/admin/vitrine/banniere", formData);
  return data;
}

export async function createContenuVitrine(formData) {
  const { data } = await api.post("/admin/vitrine/contenus", formData);
  return data;
}

export async function updateContenuVitrine(id, formData) {
  const { data } = await api.post(`/admin/vitrine/contenus/${id}`, formData);
  return data;
}

export async function deleteContenuVitrine(id) {
  const { data } = await api.delete(`/admin/vitrine/contenus/${id}`);
  return data;
}

import api from "./api";

// --- Authentification administrateur ---

export async function login(identifiant, motDePasse, codeAdmin) {
  const { data } = await api.post("/admin/login", {
    identifiant,
    mot_de_passe: motDePasse,
    code_admin: codeAdmin,
  });
  if (data.token) {
    localStorage.setItem("admin_token", data.token);
    localStorage.setItem("admin", JSON.stringify(data.admin));
  }
  return data;
}

export async function logout() {
  try {
    await api.post("/admin/logout");
  } finally {
    localStorage.removeItem("admin_token");
    localStorage.removeItem("admin");
  }
}

export async function me() {
  const { data } = await api.get("/admin/me");
  return data;
}

export function getAdmin() {
  const brut = localStorage.getItem("admin");
  return brut ? JSON.parse(brut) : null;
}

export function isAuthenticated() {
  return Boolean(localStorage.getItem("admin_token"));
}

// --- Demandes ---

export async function getCommandes(params = {}) {
  const { data } = await api.get("/admin/commandes", { params });
  return data;
}

export async function getCommande(id) {
  const { data } = await api.get(`/admin/commandes/${id}`);
  return data;
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
  return data;
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

// --- Disponibilités ---

export async function getDisponibilites(params = {}) {
  const { data } = await api.get("/admin/disponibilites", { params });
  return data;
}

// --- Gestion du catalogue (matériels / prestations / éléments) ---
// Les mises à jour sont envoyées en POST multipart (upload de photo) : les routes
// Laravel acceptent POST en plus de PUT/PATCH sur les endpoints de mise à jour.

export async function getAdminMateriels() {
  const { data } = await api.get("/admin/materiels");
  return data;
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
  return data;
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
  return data;
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
  return data;
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

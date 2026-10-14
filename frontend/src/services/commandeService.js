import api from "./api";

// Soumet une commande (FormData pour supporter la photo de pièce d'identité).
export async function createCommande(formData) {
  const { data } = await api.post("/commandes", formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return data;
}

// Vérifie la disponibilité d'un créneau de location.
export async function verifierDisponibilite(dateDebut, dateFin) {
  const { data } = await api.get("/disponibilites/verifier", {
    params: { date_debut: dateDebut, date_fin: dateFin },
  });
  return data;
}

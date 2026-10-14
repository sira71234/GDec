import api from "./api";

// Récupère tout le catalogue (matériel + prestations + éléments) en un appel.
export async function getCatalogue() {
  const { data } = await api.get("/catalogue");
  return data;
}

export async function getMateriels() {
  const { data } = await api.get("/catalogue/materiels");
  return data;
}

export async function getPrestationsDecoration() {
  const { data } = await api.get("/catalogue/prestations-decoration");
  return data;
}

export async function getElementsDecor() {
  const { data } = await api.get("/catalogue/elements-decor");
  return data;
}

// Contenu public de la vitrine (textes + images de la page d'accueil).
export async function getVitrine() {
  const { data } = await api.get("/vitrine");
  return data;
}

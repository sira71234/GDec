import api, { asArray, asObject } from "./api";

// Récupère tout le catalogue (matériel + prestations + éléments) en un appel.
export async function getCatalogue() {
  const { data } = await api.get("/catalogue");
  return {
    materiels: asArray(data?.materiels),
    prestations_decoration: asArray(data?.prestations_decoration),
    elements_decor: asArray(data?.elements_decor),
  };
}

export async function getMateriels() {
  const { data } = await api.get("/catalogue/materiels");
  return asArray(data);
}

export async function getPrestationsDecoration() {
  const { data } = await api.get("/catalogue/prestations-decoration");
  return asArray(data);
}

export async function getElementsDecor() {
  const { data } = await api.get("/catalogue/elements-decor");
  return asArray(data);
}

// Contenu public de la vitrine (textes + images de la page d'accueil).
export async function getVitrine() {
  const { data } = await api.get("/vitrine");
  return {
    ...asObject(data),
    banniere: asObject(data?.banniere),
    galerie: asArray(data?.galerie),
    sections: asArray(data?.sections),
  };
}

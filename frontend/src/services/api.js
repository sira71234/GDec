import axios from "axios";
import { effacerSession, lireJeton } from "./authStorage";

// URL de base de l'API Laravel (surchargeable via .env : VITE_API_URL).
export const API_URL = import.meta.env.VITE_API_URL || "http://localhost:8000/api";
export const STORAGE_URL = import.meta.env.VITE_STORAGE_URL || "http://localhost:8000/storage";

const api = axios.create({
  baseURL: API_URL,
  // Sans délai maximal, une requête bloquée par l'hébergeur laisse
  // l'utilisateur devant un formulaire figé sans message.
  timeout: 20000,
  headers: {
    Accept: "application/json",
  },
});

// Ajoute automatiquement le jeton admin à chaque requête protégée.
api.interceptors.request.use((config) => {
  const token = lireJeton();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Déconnecte l'admin automatiquement si le jeton est expiré/invalide.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      effacerSession();
    }
    return Promise.reject(error);
  },
);

// Garantit qu'une réponse d'API exploitable comme liste est bien un tableau.
// Protège l'interface quand le serveur renvoie une page HTML (suspension,
// erreur proxy, 404) au lieu du JSON attendu.
export function asArray(valeur) {
  return Array.isArray(valeur) ? valeur : [];
}

// Garantit qu'une réponse d'API est bien un objet exploitable.
export function asObject(valeur) {
  return valeur && typeof valeur === "object" && !Array.isArray(valeur)
    ? valeur
    : {};
}

// Construit l'URL publique d'une photo stockée sur le disque "public".
export function storageUrl(chemin) {
  if (!chemin) {
    return null;
  }
  if (chemin.startsWith("http")) {
    return chemin;
  }
  return `${STORAGE_URL}/${chemin}`;
}

export default api;

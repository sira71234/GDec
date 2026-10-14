import axios from "axios";

// URL de base de l'API Laravel (surchargeable via .env : VITE_API_URL).
export const API_URL = import.meta.env.VITE_API_URL || "http://localhost:8000/api";
export const STORAGE_URL = import.meta.env.VITE_STORAGE_URL || "http://localhost:8000/storage";

const api = axios.create({
  baseURL: API_URL,
  headers: {
    Accept: "application/json",
  },
});

// Ajoute automatiquement le jeton admin à chaque requête protégée.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("admin_token");
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
      localStorage.removeItem("admin_token");
    }
    return Promise.reject(error);
  },
);

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

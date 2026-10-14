import { useContext } from "react";
import { PanierContext } from "./panierContext";

// Hook d'accès au panier. Isolé dans son fichier pour le Fast Refresh.
export function usePanier() {
  const contexte = useContext(PanierContext);
  if (!contexte) {
    throw new Error("usePanier doit être utilisé dans un <PanierProvider>.");
  }
  return contexte;
}

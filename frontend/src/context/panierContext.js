import { createContext } from "react";

// Objet de contexte isolé dans son propre fichier pour préserver le Fast Refresh
// (un module qui exporte un composant ne doit exporter que des composants).
export const PanierContext = createContext(null);

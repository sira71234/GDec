import { useMemo, useState } from "react";
import { PanierContext } from "./panierContext";

const CLIENT_VIDE = { nom: "", telephone: "", email: "", adresse: "" };
const LOCATION_VIDE = {
  date_debut_location: "",
  date_fin_location: "",
  piece_identite_type: "",
  piece_identite_photo: null,
};
const DECORATION_VIDE = {
  type_evenement: "",
  date_evenement: "",
  duree_decoration: "1",
  theme: "",
  couleurs: "",
  nombre_personnes: "",
  complement: "",
};

export function PanierProvider({ children }) {
  const [materiels, setMateriels] = useState([]);
  const [prestations, setPrestations] = useState([]);
  const [elements, setElements] = useState([]);
  const [client, setClient] = useState(CLIENT_VIDE);
  const [location, setLocation] = useState(LOCATION_VIDE);
  const [decoration, setDecoration] = useState(DECORATION_VIDE);

  const addMateriel = (article, quantite = 1) => {
    setMateriels((precedent) => {
      const existant = precedent.find((item) => item.id === article.id);
      if (existant) {
        return precedent.map((item) =>
          item.id === article.id
            ? { ...item, quantite: item.quantite + quantite }
            : item,
        );
      }
      return [...precedent, { ...article, quantite }];
    });
  };

  const updateQuantiteMateriel = (id, quantite) => {
    const q = Math.max(1, Number(quantite) || 1);
    setMateriels((precedent) =>
      precedent.map((item) => (item.id === id ? { ...item, quantite: q } : item)),
    );
  };

  const removeMateriel = (id) =>
    setMateriels((precedent) => precedent.filter((item) => item.id !== id));

  const addPrestation = (prestation, quantite = 1) => {
    setPrestations((precedent) => {
      const existant = precedent.find((item) => item.id === prestation.id);
      if (existant) {
        return precedent.map((item) =>
          item.id === prestation.id
            ? { ...item, quantite: item.quantite + quantite }
            : item,
        );
      }
      return [...precedent, { ...prestation, quantite }];
    });
  };

  const updateQuantitePrestation = (id, quantite) => {
    const q = Math.max(1, Number(quantite) || 1);
    setPrestations((precedent) =>
      precedent.map((item) => (item.id === id ? { ...item, quantite: q } : item)),
    );
  };

  const removePrestation = (id) =>
    setPrestations((precedent) => precedent.filter((item) => item.id !== id));

  const toggleElement = (element) => {
    setElements((precedent) => {
      const present = precedent.some((item) => item.id === element.id);
      return present
        ? precedent.filter((item) => item.id !== element.id)
        : [...precedent, element];
    });
  };

  const isElementSelected = (id) => elements.some((item) => item.id === id);

  const updateClient = (champ, valeur) =>
    setClient((precedent) => ({ ...precedent, [champ]: valeur }));
  const updateLocation = (champ, valeur) =>
    setLocation((precedent) => ({ ...precedent, [champ]: valeur }));
  const updateDecoration = (champ, valeur) =>
    setDecoration((precedent) => ({ ...precedent, [champ]: valeur }));

  const clear = () => {
    setMateriels([]);
    setPrestations([]);
    setElements([]);
    setClient(CLIENT_VIDE);
    setLocation(LOCATION_VIDE);
    setDecoration(DECORATION_VIDE);
  };

  const totalMateriel = useMemo(
    () =>
      materiels.reduce(
        (acc, item) => acc + Number(item.prix_unitaire) * item.quantite,
        0,
      ),
    [materiels],
  );

  const totalPrestation = useMemo(
    () =>
      prestations.reduce(
        (acc, item) => acc + Number(item.prix) * item.quantite,
        0,
      ),
    [prestations],
  );

  const nombreArticles = useMemo(
    () =>
      materiels.reduce((acc, item) => acc + item.quantite, 0) +
      prestations.reduce((acc, item) => acc + item.quantite, 0),
    [materiels, prestations],
  );

  const typePrestation = useMemo(() => {
    const aLocation = materiels.length > 0;
    const aDecoration =
      prestations.length > 0 ||
      elements.length > 0 ||
      Boolean(decoration.type_evenement);
    if (aLocation && aDecoration) {
      return "les_deux";
    }
    if (aLocation) {
      return "location";
    }
    return "decoration";
  }, [materiels, prestations, elements, decoration.type_evenement]);

  const value = {
    materiels,
    prestations,
    elements,
    client,
    location,
    decoration,
    addMateriel,
    updateQuantiteMateriel,
    removeMateriel,
    addPrestation,
    updateQuantitePrestation,
    removePrestation,
    toggleElement,
    isElementSelected,
    updateClient,
    updateLocation,
    updateDecoration,
    clear,
    totalMateriel,
    totalPrestation,
    totalGeneral: totalMateriel + totalPrestation,
    cautionEstimee: totalMateriel * 0.3,
    nombreArticles,
    typePrestation,
  };

  return <PanierContext.Provider value={value}>{children}</PanierContext.Provider>;
}

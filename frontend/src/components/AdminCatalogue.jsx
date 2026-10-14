import { useCallback, useEffect, useState } from "react";
import PhotoUploader from "./PhotoUploader";
import { storageUrl } from "../services/api";
import {
  getAdminMateriels,
  createMateriel,
  updateMateriel,
  deleteMateriel,
  getAdminPrestations,
  createPrestation,
  updatePrestation,
  deletePrestation,
  getAdminElements,
  createElement,
  updateElement,
  deleteElement,
} from "../services/adminService";

// Description des trois entités du catalogue : champs, libellés et opérations CRUD.
const CONFIGURATIONS = {
  materiels: {
    libelle: "Matériels",
    lister: getAdminMateriels,
    creer: createMateriel,
    modifier: updateMateriel,
    supprimer: deleteMateriel,
    photo: true,
    champs: [
      { nom: "nom", libelle: "Nom", type: "text", requis: true },
      { nom: "description", libelle: "Description", type: "textarea" },
      { nom: "prix_unitaire", libelle: "Prix unitaire (FCFA)", type: "number", requis: true },
      { nom: "quantite_stock", libelle: "Quantité en stock", type: "number", requis: true },
    ],
  },
  prestations: {
    libelle: "Prestations de décoration",
    lister: getAdminPrestations,
    creer: createPrestation,
    modifier: updatePrestation,
    supprimer: deletePrestation,
    photo: true,
    champs: [
      { nom: "nom", libelle: "Nom", type: "text", requis: true },
      { nom: "description", libelle: "Description", type: "textarea" },
      { nom: "prix", libelle: "Prix (FCFA)", type: "number", requis: true },
    ],
  },
  elements: {
    libelle: "Éléments de décoration",
    lister: getAdminElements,
    creer: createElement,
    modifier: updateElement,
    supprimer: deleteElement,
    photo: false,
    champs: [
      { nom: "nom", libelle: "Nom", type: "text", requis: true },
      { nom: "description", libelle: "Description", type: "textarea" },
    ],
  },
};

const CLEFS = Object.keys(CONFIGURATIONS);

function formaterPrix(valeur) {
  return valeur ? `${Number(valeur).toLocaleString("fr-FR")} FCFA` : "";
}

function AdminCatalogue() {
  const [onglet, setOnglet] = useState(CLEFS[0]);
  const [liste, setListe] = useState([]);
  const [chargement, setChargement] = useState(true);
  const [erreur, setErreur] = useState(null);
  const [message, setMessage] = useState(null);
  const [formulaire, setFormulaire] = useState(null);
  const [fichier, setFichier] = useState(null);

  const configuration = CONFIGURATIONS[onglet];

  const charger = useCallback(async () => {
    setChargement(true);
    setErreur(null);
    try {
      const donnees = await CONFIGURATIONS[onglet].lister();
      setListe(Array.isArray(donnees) ? donnees : donnees.data ?? []);
    } catch {
      setErreur("Impossible de charger le catalogue.");
    } finally {
      setChargement(false);
    }
  }, [onglet]);

  useEffect(() => {
    charger();
  }, [charger]);

  const changerOnglet = (cle) => {
    setOnglet(cle);
    setFormulaire(null);
    setFichier(null);
    setMessage(null);
    setErreur(null);
  };

  const ouvrirCreation = () => {
    const vide = {};
    configuration.champs.forEach((champ) => {
      vide[champ.nom] = "";
    });
    setFormulaire(vide);
    setFichier(null);
    setMessage(null);
  };

  const ouvrirEdition = (element) => {
    const valeurs = { id: element.id, photo: element.photo ?? null };
    configuration.champs.forEach((champ) => {
      valeurs[champ.nom] = element[champ.nom] ?? "";
    });
    setFormulaire(valeurs);
    setFichier(null);
    setMessage(null);
  };

  const fermerFormulaire = () => {
    setFormulaire(null);
    setFichier(null);
  };

  const soumettre = async (event) => {
    event.preventDefault();
    setErreur(null);
    setMessage(null);

    const donnees = new FormData();
    configuration.champs.forEach((champ) => {
      donnees.append(champ.nom, formulaire[champ.nom] ?? "");
    });
    if (configuration.photo && fichier) {
      donnees.append("photo", fichier);
    }

    try {
      if (formulaire.id) {
        await configuration.modifier(formulaire.id, donnees);
        setMessage("Élément mis à jour.");
      } else {
        await configuration.creer(donnees);
        setMessage("Élément ajouté au catalogue.");
      }
      fermerFormulaire();
      await charger();
    } catch (err) {
      const details = err.response?.data?.errors;
      const premier = details ? Object.values(details)[0]?.[0] : null;
      setErreur(premier || err.response?.data?.message || "Enregistrement impossible.");
    }
  };

  const supprimer = async (element) => {
    if (!window.confirm(`Retirer « ${element.nom} » du catalogue ?`)) {
      return;
    }
    setErreur(null);
    try {
      await configuration.supprimer(element.id);
      await charger();
    } catch {
      setErreur("Suppression impossible.");
    }
  };

  return (
    <div>
      <div className="flex flex-wrap gap-2 mb-4">
        {CLEFS.map((cle) => (
          <button
            key={cle}
            type="button"
            onClick={() => changerOnglet(cle)}
            className={`px-3 py-1 rounded-full text-sm font-semibold ${
              onglet === cle
                ? "bg-emerald-100 text-emerald-800"
                : "bg-gray-100 text-gray-600 hover:bg-gray-200"
            }`}
          >
            {CONFIGURATIONS[cle].libelle}
          </button>
        ))}
      </div>

      <div className="flex items-center justify-between mb-4">
        <h3 className="font-bold text-emerald-800">{configuration.libelle}</h3>
        <button
          type="button"
          onClick={ouvrirCreation}
          className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-lg"
        >
          + Ajouter
        </button>
      </div>

      {erreur && <p className="text-red-600 mb-3">{erreur}</p>}
      {message && <p className="text-emerald-700 mb-3">{message}</p>}

      {formulaire && (
        <form
          onSubmit={soumettre}
          className="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-6 space-y-3"
        >
          <h4 className="font-semibold text-gray-700">
            {formulaire.id ? "Modifier" : "Nouvel élément"}
          </h4>

          {configuration.champs.map((champ) => (
            <div key={champ.nom}>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                {champ.libelle}
                {champ.requis && <span className="text-red-500"> *</span>}
              </label>
              {champ.type === "textarea" ? (
                <textarea
                  value={formulaire[champ.nom]}
                  onChange={(e) =>
                    setFormulaire({ ...formulaire, [champ.nom]: e.target.value })
                  }
                  rows={2}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2"
                />
              ) : (
                <input
                  type={champ.type}
                  value={formulaire[champ.nom]}
                  onChange={(e) =>
                    setFormulaire({ ...formulaire, [champ.nom]: e.target.value })
                  }
                  required={champ.requis}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2"
                />
              )}
            </div>
          ))}

          {configuration.photo && (
            <PhotoUploader
              label="Photo"
              fichier={fichier}
              onFichier={setFichier}
              photoExistante={formulaire.id ? formulaire.photo : null}
              requis={!formulaire.id}
            />
          )}

          <div className="flex gap-2">
            <button
              type="submit"
              className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-lg"
            >
              Enregistrer
            </button>
            <button
              type="button"
              onClick={fermerFormulaire}
              className="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-4 py-2 rounded-lg"
            >
              Annuler
            </button>
          </div>
        </form>
      )}

      {chargement ? (
        <p className="text-gray-500">Chargement…</p>
      ) : liste.length === 0 ? (
        <p className="text-gray-500">Aucun élément pour le moment.</p>
      ) : (
        <ul className="divide-y divide-gray-100">
          {liste.map((element) => (
            <li key={element.id} className="flex items-center gap-3 py-3">
              {configuration.photo && element.photo && (
                <img
                  src={storageUrl(element.photo)}
                  alt={element.nom}
                  className="h-12 w-12 rounded object-cover border border-gray-200"
                />
              )}
              <div className="flex-1">
                <p className="font-semibold text-gray-800">{element.nom}</p>
                <p className="text-sm text-gray-500">
                  {formaterPrix(element.prix_unitaire ?? element.prix)}
                  {element.quantite_stock != null &&
                    ` · stock : ${element.quantite_stock}`}
                </p>
              </div>
              <button
                type="button"
                onClick={() => ouvrirEdition(element)}
                className="text-emerald-700 hover:underline text-sm font-semibold"
              >
                Modifier
              </button>
              <button
                type="button"
                onClick={() => supprimer(element)}
                className="text-red-600 hover:underline text-sm font-semibold"
              >
                Supprimer
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

export default AdminCatalogue;

import { useCallback, useEffect, useState } from "react";
import PhotoUploader from "./PhotoUploader";
import { storageUrl } from "../services/api";
import {
  getAdminVitrine,
  updateBanniere,
  createContenuVitrine,
  updateContenuVitrine,
  deleteContenuVitrine,
} from "../services/adminService";

function AdminVitrine() {
  const [vitrine, setVitrine] = useState({ banniere: {}, galerie: [], sections: [] });
  const [chargement, setChargement] = useState(true);
  const [erreur, setErreur] = useState(null);
  const [message, setMessage] = useState(null);

  const [banniere, setBanniere] = useState({ titre: "", sous_titre: "" });
  const [banniereImage, setBanniereImage] = useState(null);

  const [imageTitre, setImageTitre] = useState("");
  const [imageFichier, setImageFichier] = useState(null);

  const [section, setSection] = useState(null);

  const charger = useCallback(async () => {
    setChargement(true);
    setErreur(null);
    try {
      const donnees = await getAdminVitrine();
      setVitrine(donnees);
      setBanniere({
        titre: donnees.banniere?.titre ?? "",
        sous_titre: donnees.banniere?.sous_titre ?? "",
      });
    } catch {
      setErreur("Impossible de charger la vitrine.");
    } finally {
      setChargement(false);
    }
  }, []);

  useEffect(() => {
    charger();
  }, [charger]);

  const enregistrerBanniere = async (event) => {
    event.preventDefault();
    setErreur(null);
    setMessage(null);

    const donnees = new FormData();
    donnees.append("titre", banniere.titre ?? "");
    donnees.append("sous_titre", banniere.sous_titre ?? "");
    if (banniereImage) {
      donnees.append("image", banniereImage);
    }

    try {
      const reponse = await updateBanniere(donnees);
      setVitrine(reponse.vitrine);
      setBanniereImage(null);
      setMessage("Bannière enregistrée.");
    } catch {
      setErreur("Enregistrement de la bannière impossible. Vérifiez les champs.");
    }
  };

  const ajouterImage = async (event) => {
    event.preventDefault();
    setErreur(null);
    setMessage(null);

    if (!imageFichier) {
      setErreur("Sélectionnez une image pour la galerie.");
      return;
    }

    const donnees = new FormData();
    donnees.append("section", "galerie");
    donnees.append("type", "image");
    donnees.append("titre", imageTitre ?? "");
    donnees.append("image", imageFichier);

    try {
      await createContenuVitrine(donnees);
      setImageTitre("");
      setImageFichier(null);
      setMessage("Image ajoutée à la galerie.");
      await charger();
    } catch {
      setErreur("Ajout de l'image impossible.");
    }
  };

  const supprimerImage = async (id) => {
    if (!window.confirm("Supprimer cette image de la galerie ?")) {
      return;
    }
    try {
      await deleteContenuVitrine(id);
      await charger();
    } catch {
      setErreur("Suppression impossible.");
    }
  };

  const soumettreSection = async (event) => {
    event.preventDefault();
    setErreur(null);
    setMessage(null);

    const donnees = new FormData();
    donnees.append("section", "section");
    donnees.append("type", "texte");
    donnees.append("titre", section.titre ?? "");
    donnees.append("valeur", section.texte ?? "");
    donnees.append("ordre", section.ordre ?? 0);

    try {
      if (section.id) {
        await updateContenuVitrine(section.id, donnees);
      } else {
        await createContenuVitrine(donnees);
      }
      setSection(null);
      setMessage("Section enregistrée.");
      await charger();
    } catch {
      setErreur("Enregistrement de la section impossible.");
    }
  };

  const supprimerSection = async (id) => {
    if (!window.confirm("Supprimer cette section ?")) {
      return;
    }
    try {
      await deleteContenuVitrine(id);
      await charger();
    } catch {
      setErreur("Suppression impossible.");
    }
  };

  if (chargement) {
    return <p className="text-gray-500">Chargement…</p>;
  }

  return (
    <div className="space-y-8">
      {erreur && <p className="text-red-600">{erreur}</p>}
      {message && <p className="text-emerald-700">{message}</p>}

      {/* Bannière */}
      <section>
        <h3 className="font-bold text-emerald-800 mb-3">Bannière d'accueil</h3>
        <form
          onSubmit={enregistrerBanniere}
          className="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3"
        >
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">
              Titre
            </label>
            <input
              type="text"
              value={banniere.titre}
              onChange={(e) => setBanniere({ ...banniere, titre: e.target.value })}
              className="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">
              Sous-titre
            </label>
            <input
              type="text"
              value={banniere.sous_titre}
              onChange={(e) => setBanniere({ ...banniere, sous_titre: e.target.value })}
              className="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
          </div>
          <PhotoUploader
            label="Image de fond (facultatif)"
            fichier={banniereImage}
            onFichier={setBanniereImage}
            photoExistante={vitrine.banniere?.image}
          />
          <button
            type="submit"
            className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-lg"
          >
            Enregistrer la bannière
          </button>
        </form>
      </section>

      {/* Galerie */}
      <section>
        <h3 className="font-bold text-emerald-800 mb-3">Galerie</h3>
        <form
          onSubmit={ajouterImage}
          className="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3 mb-4"
        >
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">
              Légende (facultatif)
            </label>
            <input
              type="text"
              value={imageTitre}
              onChange={(e) => setImageTitre(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
          </div>
          <PhotoUploader
            label="Image"
            fichier={imageFichier}
            onFichier={setImageFichier}
            requis
          />
          <button
            type="submit"
            className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-lg"
          >
            Ajouter à la galerie
          </button>
        </form>

        {vitrine.galerie.length === 0 ? (
          <p className="text-gray-500">Aucune image dans la galerie.</p>
        ) : (
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {vitrine.galerie.map((item) => (
              <div key={item.id} className="relative group">
                <img
                  src={storageUrl(item.image)}
                  alt={item.titre || "Galerie"}
                  className="h-32 w-full rounded-lg object-cover border border-gray-200"
                />
                {item.titre && (
                  <p className="text-sm text-gray-600 mt-1 truncate">{item.titre}</p>
                )}
                <button
                  type="button"
                  onClick={() => supprimerImage(item.id)}
                  className="absolute top-1 right-1 bg-red-600 text-white text-xs font-semibold px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition"
                >
                  Supprimer
                </button>
              </div>
            ))}
          </div>
        )}
      </section>

      {/* Sections libres */}
      <section>
        <div className="flex items-center justify-between mb-3">
          <h3 className="font-bold text-emerald-800">Sections de texte</h3>
          <button
            type="button"
            onClick={() => setSection({ titre: "", texte: "", ordre: 0 })}
            className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-lg"
          >
            + Ajouter une section
          </button>
        </div>

        {section && (
          <form
            onSubmit={soumettreSection}
            className="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3 mb-4"
          >
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Titre
              </label>
              <input
                type="text"
                value={section.titre}
                onChange={(e) => setSection({ ...section, titre: e.target.value })}
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Texte
              </label>
              <textarea
                value={section.texte}
                onChange={(e) => setSection({ ...section, texte: e.target.value })}
                rows={3}
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">
                Ordre d'affichage
              </label>
              <input
                type="number"
                value={section.ordre}
                onChange={(e) => setSection({ ...section, ordre: e.target.value })}
                className="w-32 border border-gray-300 rounded-lg px-3 py-2"
              />
            </div>
            <div className="flex gap-2">
              <button
                type="submit"
                className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-lg"
              >
                Enregistrer
              </button>
              <button
                type="button"
                onClick={() => setSection(null)}
                className="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-4 py-2 rounded-lg"
              >
                Annuler
              </button>
            </div>
          </form>
        )}

        {vitrine.sections.length === 0 ? (
          <p className="text-gray-500">Aucune section de texte.</p>
        ) : (
          <ul className="divide-y divide-gray-100">
            {vitrine.sections.map((item) => (
              <li key={item.id} className="flex items-start gap-3 py-3">
                <div className="flex-1">
                  <p className="font-semibold text-gray-800">
                    {item.titre || "(sans titre)"}
                  </p>
                  <p className="text-sm text-gray-500">{item.texte}</p>
                </div>
                <button
                  type="button"
                  onClick={() =>
                    setSection({ id: item.id, titre: item.titre ?? "", texte: item.texte ?? "", ordre: item.ordre ?? 0 })
                  }
                  className="text-emerald-700 hover:underline text-sm font-semibold"
                >
                  Modifier
                </button>
                <button
                  type="button"
                  onClick={() => supprimerSection(item.id)}
                  className="text-red-600 hover:underline text-sm font-semibold"
                >
                  Supprimer
                </button>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}

export default AdminVitrine;

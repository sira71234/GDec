import { useState } from "react";
import { usePanier } from "../context/usePanier";
import { createCommande } from "../services/commandeService";

function FormulaireCoordonnees() {
  const {
    client,
    updateClient,
    location,
    decoration,
    materiels,
    prestations,
    elements,
    typePrestation,
    totalGeneral,
    clear,
  } = usePanier();

  const [envoi, setEnvoi] = useState(false);
  const [succes, setSucces] = useState(null);
  const [erreur, setErreur] = useState(null);
  const [erreursApi, setErreursApi] = useState({});

  const construitFormData = () => {
    const data = new FormData();
    data.append("type_prestation", typePrestation);
    data.append("client[nom]", client.nom);
    data.append("client[telephone]", client.telephone);
    if (client.email) {
      data.append("client[email]", client.email);
    }
    if (client.adresse) {
      data.append("client[adresse]", client.adresse);
    }

    if (typePrestation === "location" || typePrestation === "les_deux") {
      data.append("date_debut_location", location.date_debut_location);
      data.append("date_fin_location", location.date_fin_location);
      data.append("piece_identite_type", location.piece_identite_type);
      if (location.piece_identite_photo) {
        data.append("piece_identite_photo", location.piece_identite_photo);
      }
    }

    if (typePrestation === "decoration" || typePrestation === "les_deux") {
      if (decoration.type_evenement) {
        data.append("type_evenement", decoration.type_evenement);
      }
      if (decoration.theme) data.append("theme", decoration.theme);
      if (decoration.couleurs) data.append("couleurs", decoration.couleurs);
      if (decoration.nombre_personnes) {
        data.append("nombre_personnes", decoration.nombre_personnes);
      }
      if (decoration.complement) {
        data.append("complement", decoration.complement);
      }
    }

    materiels.forEach((item, index) => {
      data.append(`materiels[${index}][id]`, item.id);
      data.append(`materiels[${index}][quantite]`, item.quantite);
    });
    prestations.forEach((item, index) => {
      data.append(`prestations_decoration[${index}][id]`, item.id);
      data.append(`prestations_decoration[${index}][quantite]`, item.quantite);
    });
    elements.forEach((element, index) => {
      data.append(`elements_decor[${index}]`, element.id);
    });

    return data;
  };

  const valider = () => {
    const erreurs = {};
    if (!client.nom.trim()) erreurs["client[nom]"] = "Le nom est obligatoire.";
    if (!client.telephone.trim()) {
      erreurs["client[telephone]"] = "Le téléphone est obligatoire.";
    }
    if (client.email && !/^\S+@\S+\.\S+$/.test(client.email)) {
      erreurs["client[email]"] = "Email invalide.";
    }
    if (typePrestation === "location" || typePrestation === "les_deux") {
      if (!location.date_debut_location) {
        erreurs["date_debut_location"] = "Date de début requise.";
      }
      if (!location.date_fin_location) {
        erreurs["date_fin_location"] = "Date de fin requise.";
      }
      if (!location.piece_identite_type) {
        erreurs["piece_identite_type"] = "Type de pièce requis.";
      }
      if (!location.piece_identite_photo) {
        erreurs["piece_identite_photo"] = "Photo de la pièce requise.";
      }
    }
    if (typePrestation === "decoration" || typePrestation === "les_deux") {
      if (!decoration.type_evenement) {
        erreurs["type_evenement"] = "Type d'événement requis.";
      }
    }
    setErreursApi(erreurs);
    return Object.keys(erreurs).length === 0;
  };

  const soumettre = async (event) => {
    event.preventDefault();
    setErreur(null);
    setSucces(null);

    const panierVide = materiels.length === 0 && prestations.length === 0 &&
      elements.length === 0 && !decoration.type_evenement;
    if (panierVide) {
      setErreur("Votre demande est vide.");
      return;
    }
    if (!valider()) {
      return;
    }

    setEnvoi(true);
    try {
      const reponse = await createCommande(construitFormData());
      setSucces(reponse);
      setErreursApi({});
      clear();
    } catch (error) {
      const details = error.response?.data?.errors;
      if (details) {
        setErreursApi(details);
      }
      setErreur(
        error.response?.data?.message ||
          "Une erreur est survenue lors de l'envoi de votre demande.",
      );
    } finally {
      setEnvoi(false);
    }
  };

  const messageChamp = (champ) => {
    const valeur = erreursApi[champ];
    if (!valeur) return null;
    return <p className="text-red-600 text-xs mt-1">{[].concat(valeur)[0]}</p>;
  };

  if (succes) {
    return (
      <section className="max-w-2xl mx-auto my-8 p-6 bg-emerald-50 border border-emerald-200 rounded-xl text-center">
        <h2 className="text-xl font-bold text-emerald-800 mb-2">
          Demande enregistrée
        </h2>
        <p className="text-gray-700 mb-2">{succes.message}</p>
        <p className="text-gray-700 mb-4">
          Caution à régler :{" "}
          <strong>
            {Number(succes.commande?.montant_caution || 0).toLocaleString("fr-FR")} FCFA
          </strong>
        </p>
        <button
          type="button"
          onClick={() => setSucces(null)}
          className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-5 py-2 rounded-lg"
        >
          Nouvelle demande
        </button>
      </section>
    );
  }

  return (
    <section className="max-w-2xl mx-auto my-8 p-6 bg-white rounded-xl shadow border border-emerald-100">
      <h2 className="text-xl font-bold text-emerald-800 mb-4">Vos coordonnées</h2>
      <form onSubmit={soumettre} className="grid gap-4 md:grid-cols-2">
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Nom complet *
          </label>
          <input
            type="text"
            value={client.nom}
            onChange={(e) => updateClient("nom", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
          {messageChamp("client[nom]")}
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Téléphone *
          </label>
          <input
            type="text"
            value={client.telephone}
            onChange={(e) => updateClient("telephone", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
          {messageChamp("client[telephone]")}
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Email
          </label>
          <input
            type="email"
            value={client.email}
            onChange={(e) => updateClient("email", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
          {messageChamp("client[email]")}
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Adresse
          </label>
          <input
            type="text"
            value={client.adresse}
            onChange={(e) => updateClient("adresse", e.target.value)}
            className="w-full border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>

        <div className="md:col-span-2 border-t border-gray-100 pt-4">
          {messageChamp("date_debut_location")}
          {messageChamp("date_fin_location")}
          {messageChamp("piece_identite_type")}
          {messageChamp("piece_identite_photo")}
          {messageChamp("type_evenement")}
        </div>

        {erreur && (
          <p className="md:col-span-2 text-red-600 font-medium">{erreur}</p>
        )}

        <div className="md:col-span-2 flex items-center justify-between gap-4 mt-2">
          <p className="text-gray-700">
            Total :{" "}
            <strong className="text-emerald-800">
              {Number(totalGeneral).toLocaleString("fr-FR")} FCFA
            </strong>
          </p>
          <button
            type="submit"
            disabled={envoi}
            className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-6 py-3 rounded-lg transition disabled:opacity-60"
          >
            {envoi ? "Envoi…" : "Valider ma demande"}
          </button>
        </div>
      </form>
    </section>
  );
}

export default FormulaireCoordonnees;

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
      data.append("type_evenement", decoration.type_evenement);
      data.append("date_evenement", decoration.date_evenement);
      data.append("duree_decoration", decoration.duree_decoration || "1");
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
    const aujourdHui = new Date().toLocaleDateString("en-CA");

    if (!client.nom.trim()) erreurs["client.nom"] = "Le nom est obligatoire.";
    if (!client.telephone.trim()) {
      erreurs["client.telephone"] = "Le numéro de téléphone est obligatoire.";
    }
    if (client.email && !/^\S+@\S+\.\S+$/.test(client.email)) {
      erreurs["client.email"] = "L'adresse email est invalide.";
    }
    if (typePrestation === "location" || typePrestation === "les_deux") {
      if (!location.date_debut_location) {
        erreurs["date_debut_location"] = "La date de début de location est obligatoire.";
      } else if (location.date_debut_location < aujourdHui) {
        erreurs["date_debut_location"] = "La date de début de location ne peut pas être antérieure à aujourd'hui.";
      }
      if (!location.date_fin_location) {
        erreurs["date_fin_location"] = "La date de fin de location est obligatoire.";
      } else if (location.date_fin_location < (location.date_debut_location || aujourdHui)) {
        erreurs["date_fin_location"] = "La date de fin de location doit être égale ou postérieure à la date de début.";
      }
      if (!location.piece_identite_type) {
        erreurs["piece_identite_type"] = "Le type de pièce d'identité est obligatoire.";
      }
      if (!location.piece_identite_photo) {
        erreurs["piece_identite_photo"] = "La photo de la pièce d'identité est obligatoire.";
      }
    }
    if (typePrestation === "decoration" || typePrestation === "les_deux") {
      if (!decoration.type_evenement) {
        erreurs["type_evenement"] = "Le type d'événement est obligatoire.";
      }
      if (!decoration.date_evenement) {
        erreurs["date_evenement"] = "La date de l'événement est obligatoire.";
      } else if (decoration.date_evenement < aujourdHui) {
        erreurs["date_evenement"] = "La date de l'événement ne peut pas être antérieure à aujourd'hui.";
      }
      if (!decoration.duree_decoration || Number(decoration.duree_decoration) < 1) {
        erreurs["duree_decoration"] = "La durée de la décoration doit être d'au moins un jour.";
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
        setErreur("Certains champs sont invalides. Merci de les corriger.");
      } else {
        // On n'expose jamais les erreurs système brutes dans l'interface.
        setErreursApi({});
        setErreur(
          "Une erreur est survenue. Veuillez réessayer dans un instant.",
        );
      }
    } finally {
      setEnvoi(false);
    }
  };

  const messageChamp = (champ) => {
    const valeur = erreursApi[champ];
    if (!valeur) return null;
    return <p className="text-red-600 text-xs mt-1">{[].concat(valeur)[0]}</p>;
  };

  // Les erreurs hors coordonnées (dates, pièce, décoration) sont regroupées
  // dans un encart pour rester visibles même plus bas dans la page.
  const champsClient = ["client.nom", "client.telephone", "client.email"];
  const autresErreurs = Object.entries(erreursApi).filter(
    ([champ]) => !champsClient.includes(champ),
  );

  if (succes) {
    return (
      <section className="max-w-2xl mx-auto my-8 p-6 bg-emerald-50 border border-emerald-200 rounded-xl text-center">
        <h2 className="text-xl font-bold text-emerald-800 mb-2">
          Demande enregistrée
        </h2>
        <p className="text-gray-700 mb-2">{succes.message}</p>
        <p className="text-gray-700 mb-1">
          Total de la demande :{" "}
          <strong>
            {Number(succes.commande?.montant_total || 0).toLocaleString("fr-FR")} FCFA
          </strong>
        </p>
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
          {messageChamp("client.nom")}
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
          {messageChamp("client.telephone")}
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
          {messageChamp("client.email")}
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

        {autresErreurs.length > 0 && (
          <div className="md:col-span-2 bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">
            <p className="font-semibold mb-1">Merci de compléter :</p>
            <ul className="list-disc list-inside space-y-0.5">
              {autresErreurs.flatMap(([champ, valeur]) =>
                [].concat(valeur).map((message, index) => (
                  <li key={`${champ}-${index}`}>{message}</li>
                )),
              )}
            </ul>
          </div>
        )}

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

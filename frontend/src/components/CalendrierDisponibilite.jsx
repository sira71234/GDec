import { useEffect, useMemo, useState } from "react";
import { getDisponibilites } from "../services/adminService";
import { couleurClient } from "../utils/couleurs";

const MOIS = [
  "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
  "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre",
];
const JOURS = ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"];

const LIBELLES_STATUT = {
  en_attente: "En attente",
  valide: "Validée",
  refuse: "Refusée",
};

function dateIso(date) {
  const annee = date.getFullYear();
  const mois = String(date.getMonth() + 1).padStart(2, "0");
  const jour = String(date.getDate()).padStart(2, "0");
  return `${annee}-${mois}-${jour}`;
}

// Convertit "2026-09-15..." en date locale (évite les décalages de fuseau).
function versDate(valeur) {
  if (!valeur) return null;
  const [annee, mois, jour] = String(valeur)
    .slice(0, 10)
    .split("-")
    .map(Number);
  if (!annee || !mois || !jour) return null;
  return new Date(annee, mois - 1, jour);
}

function construireEvenements(reservations) {
  const parJour = {};

  const ajouter = (date, evenement) => {
    const cle = dateIso(date);
    if (!parJour[cle]) parJour[cle] = [];
    parJour[cle].push(evenement);
  };

  reservations.forEach((reservation) => {
    const estLocation =
      reservation.type_prestation === "location" ||
      reservation.type_prestation === "les_deux";
    const estDecoration =
      reservation.type_prestation === "decoration" ||
      reservation.type_prestation === "les_deux";

    const base = {
      commandeId: reservation.id,
      clientNom: reservation.client?.nom ?? `Client #${reservation.client_id}`,
      statut: reservation.statut,
      couleur: couleurClient(reservation.client_id),
      typeEvenement: reservation.type_evenement,
    };

    if (estLocation && reservation.date_debut_location && reservation.date_fin_location) {
      const debut = versDate(reservation.date_debut_location);
      const fin = versDate(reservation.date_fin_location);
      if (debut && fin) {
        for (let d = new Date(debut); d <= fin; d.setDate(d.getDate() + 1)) {
          ajouter(d, { ...base, type: "location" });
        }
      }
    }

    if (estDecoration && reservation.date_evenement) {
      const debut = versDate(reservation.date_evenement);
      const duree = Math.max(1, Number(reservation.duree_decoration) || 1);
      if (debut) {
        for (let i = 0; i < duree; i += 1) {
          const d = new Date(debut);
          d.setDate(d.getDate() + i);
          ajouter(d, { ...base, type: "decoration" });
        }
      }
    }
  });

  return parJour;
}

function CalendrierDisponibilite() {
  const aujourdHui = new Date();
  const [mois, setMois] = useState(aujourdHui.getMonth() + 1);
  const [annee, setAnnee] = useState(aujourdHui.getFullYear());
  const [reservations, setReservations] = useState([]);
  const [chargement, setChargement] = useState(true);
  const [erreur, setErreur] = useState(null);

  useEffect(() => {
    getDisponibilites()
      .then(setReservations)
      .catch(() => setErreur("Impossible de charger les disponibilités."))
      .finally(() => setChargement(false));
  }, []);

  const evenementsParJour = useMemo(
    () => construireEvenements(reservations),
    [reservations],
  );

  const clients = useMemo(() => {
    const uniques = new Map();
    reservations.forEach((reservation) => {
      if (!uniques.has(reservation.client_id)) {
        uniques.set(reservation.client_id, {
          id: reservation.client_id,
          nom: reservation.client?.nom ?? `Client #${reservation.client_id}`,
          couleur: couleurClient(reservation.client_id),
        });
      }
    });
    return [...uniques.values()];
  }, [reservations]);

  const changerMois = (delta) => {
    let nouveauMois = mois + delta;
    let nouvelleAnnee = annee;
    if (nouveauMois < 1) {
      nouveauMois = 12;
      nouvelleAnnee -= 1;
    } else if (nouveauMois > 12) {
      nouveauMois = 1;
      nouvelleAnnee += 1;
    }
    setMois(nouveauMois);
    setAnnee(nouvelleAnnee);
  };

  const premierJour = new Date(annee, mois - 1, 1);
  const nombreJours = new Date(annee, mois, 0).getDate();
  // getDay() : 0 = dimanche ; on décale pour une semaine commençant lundi.
  const decalage = (premierJour.getDay() + 6) % 7;
  const cases = [
    ...Array(decalage).fill(null),
    ...Array.from({ length: nombreJours }, (_, i) => i + 1),
  ];

  let evenementsDuMois = 0;
  let joursOccupesDuMois = 0;
  const prefixeMois = `${annee}-${String(mois).padStart(2, "0")}`;
  Object.entries(evenementsParJour).forEach(([cle, liste]) => {
    if (cle.startsWith(prefixeMois)) {
      evenementsDuMois += liste.length;
      joursOccupesDuMois += 1;
    }
  });

  const aujourdHuiIso = dateIso(aujourdHui);

  return (
    <div>
      <div className="flex items-center justify-between mb-4">
        <button
          type="button"
          onClick={() => changerMois(-1)}
          className="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200"
        >
          ‹ Précédent
        </button>
        <div className="text-center">
          <h3 className="font-bold text-emerald-800 text-lg">
            {MOIS[mois - 1]} {annee}
          </h3>
          <p className="text-xs text-gray-500">
            {evenementsDuMois} événement(s) — {joursOccupesDuMois} jour(s) occupé(s)
          </p>
        </div>
        <button
          type="button"
          onClick={() => changerMois(1)}
          className="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200"
        >
          Suivant ›
        </button>
      </div>

      {erreur && <p className="text-red-600 mb-3">{erreur}</p>}
      {chargement && <p className="text-gray-500 mb-3">Chargement…</p>}

      <div className="overflow-x-auto">
        <div className="min-w-[860px]">
          <div className="grid grid-cols-7 gap-1 text-center text-sm">
            {JOURS.map((jour) => (
              <div key={jour} className="font-semibold text-gray-500 py-1">
                {jour}
              </div>
            ))}
            {cases.map((jour, index) => {
              if (jour === null) {
                return <div key={`vide-${index}`} />;
              }
              const iso = `${prefixeMois}-${String(jour).padStart(2, "0")}`;
              const evenements = evenementsParJour[iso] ?? [];
              const estAujourdHui = iso === aujourdHuiIso;

              return (
                <div
                  key={iso}
                  className={`min-h-[120px] rounded-lg border p-1 flex flex-col gap-0.5 text-left ${
                    estAujourdHui
                      ? "border-emerald-500 bg-emerald-50/50"
                      : evenements.length > 0
                        ? "border-gray-300 bg-white"
                        : "border-gray-200 bg-white"
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <span
                      className={`text-xs font-semibold ${
                        estAujourdHui ? "text-emerald-700" : "text-gray-600"
                      }`}
                    >
                      {jour}
                    </span>
                    {evenements.length > 0 && (
                      <span className="text-[10px] font-bold rounded-full bg-gray-700 text-white px-1.5 py-0.5">
                        {evenements.length}
                      </span>
                    )}
                  </div>

                  {evenements.map((evenement, position) => {
                    const estLocation = evenement.type === "location";
                    return (
                      <div
                        key={`${evenement.commandeId}-${evenement.type}-${position}`}
                        className={`flex items-center gap-1 px-1 py-0.5 text-[10px] leading-tight ${
                          evenement.couleur.fond
                        } ${evenement.couleur.texte} ${
                          estLocation
                            ? `border-l-4 border-solid ${evenement.couleur.bord}`
                            : `border border-dashed ${evenement.couleur.bord} rounded-full`
                        }`}
                        title={`${evenement.clientNom} — ${
                          estLocation ? "Location" : "Décoration"
                        } — ${LIBELLES_STATUT[evenement.statut] ?? evenement.statut}${
                          evenement.typeEvenement
                            ? ` — ${evenement.typeEvenement}`
                            : ""
                        }`}
                      >
                        <span
                          className={`shrink-0 font-bold ${
                            estLocation ? "rounded-sm" : "rounded-full"
                          } bg-white/70 px-1`}
                        >
                          {estLocation ? "L" : "D"}
                        </span>
                        <span className="truncate">{evenement.clientNom}</span>
                      </div>
                    );
                  })}
                </div>
              );
            })}
          </div>
        </div>
      </div>

      <div className="mt-4 space-y-2 text-xs text-gray-600">
        <div className="flex flex-wrap gap-4">
          <span className="flex items-center gap-1">
            <span className="w-4 h-3 border-l-4 border-solid border-emerald-500 bg-emerald-100 rounded-sm" />
            L = Location (trait plein)
          </span>
          <span className="flex items-center gap-1">
            <span className="w-4 h-3 border border-dashed border-emerald-500 bg-emerald-100 rounded-full" />
            D = Décoration (pointillés)
          </span>
          <span className="flex items-center gap-1">
            <span className="w-3 h-3 bg-gray-700 rounded-full" />
            Le chiffre indique le nombre d'événements du jour
          </span>
        </div>

        {clients.length > 0 && (
          <div className="flex flex-wrap gap-3 pt-1 border-t border-gray-100">
            <span className="font-semibold text-gray-500">Clients :</span>
            {clients.map((client) => (
              <span key={client.id} className="flex items-center gap-1">
                <span
                  className={`w-3 h-3 rounded-full ${client.couleur.pastille}`}
                />
                {client.nom}
              </span>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

export default CalendrierDisponibilite;

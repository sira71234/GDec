import { useEffect, useMemo, useState } from "react";
import { getDisponibilites } from "../services/adminService";

const MOIS = [
  "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
  "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre",
];
const JOURS = ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"];

function dateIso(date) {
  const annee = date.getFullYear();
  const mois = String(date.getMonth() + 1).padStart(2, "0");
  const jour = String(date.getDate()).padStart(2, "0");
  return `${annee}-${mois}-${jour}`;
}

function CalendrierDisponibilite() {
  const aujourdHui = new Date();
  const [mois, setMois] = useState(aujourdHui.getMonth() + 1);
  const [annee, setAnnee] = useState(aujourdHui.getFullYear());
  const [reservations, setReservations] = useState([]);
  const [erreur, setErreur] = useState(null);

  useEffect(() => {
    getDisponibilites({ mois, annee })
      .then(setReservations)
      .catch(() => setErreur("Impossible de charger les disponibilités."));
  }, [mois, annee]);

  // Jours réservés (créneaux validés ou en attente).
  const joursReserves = useMemo(() => {
    const ensemble = new Set();
    reservations.forEach((reservation) => {
      if (!reservation.date_debut_location || !reservation.date_fin_location) {
        return;
      }
      const debut = new Date(reservation.date_debut_location);
      const fin = new Date(reservation.date_fin_location);
      for (let d = new Date(debut); d <= fin; d.setDate(d.getDate() + 1)) {
        ensemble.add(dateIso(new Date(d)));
      }
    });
    return ensemble;
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
        <h3 className="font-bold text-emerald-800">
          {MOIS[mois - 1]} {annee}
        </h3>
        <button
          type="button"
          onClick={() => changerMois(1)}
          className="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200"
        >
          Suivant ›
        </button>
      </div>

      {erreur && <p className="text-red-600 mb-3">{erreur}</p>}

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
          const iso = `${annee}-${String(mois).padStart(2, "0")}-${String(jour).padStart(2, "0")}`;
          const reserve = joursReserves.has(iso);
          return (
            <div
              key={iso}
              className={`py-2 rounded-lg border ${
                reserve
                  ? "bg-red-100 border-red-300 text-red-700 font-semibold"
                  : "bg-white border-gray-200 text-gray-700"
              }`}
              title={reserve ? "Réservé" : "Disponible"}
            >
              {jour}
            </div>
          );
        })}
      </div>

      <div className="flex gap-4 mt-4 text-xs text-gray-600">
        <span className="flex items-center gap-1">
          <span className="w-3 h-3 bg-red-100 border border-red-300 rounded" />
          Réservé ({joursReserves.size} jour(s))
        </span>
        <span className="flex items-center gap-1">
          <span className="w-3 h-3 bg-white border border-gray-200 rounded" />
          Disponible
        </span>
      </div>
    </div>
  );
}

export default CalendrierDisponibilite;

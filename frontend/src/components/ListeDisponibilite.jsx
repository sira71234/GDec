import { useEffect, useState } from "react";
import { getDisponibilites } from "../services/adminService";
import {
  couleurClient,
  LIBELLES_TYPE_PRESTATION,
} from "../utils/couleurs";

const LIBELLES_STATUT = {
  en_attente: "En attente",
  valide: "Validée",
  refuse: "Refusée",
};

const dateCourte = (valeur) => (valeur ? String(valeur).slice(0, 10) : "—");

function resumerDates(reservation) {
  const lignes = [];
  const estLocation =
    reservation.type_prestation === "location" ||
    reservation.type_prestation === "les_deux";
  const estDecoration =
    reservation.type_prestation === "decoration" ||
    reservation.type_prestation === "les_deux";

  if (estLocation && reservation.date_debut_location) {
    lignes.push(
      `Location : ${dateCourte(reservation.date_debut_location)} → ${dateCourte(
        reservation.date_fin_location,
      )}`,
    );
  }

  if (estDecoration && reservation.date_evenement) {
    lignes.push(
      `Décoration : ${dateCourte(reservation.date_evenement)}${
        reservation.duree_decoration
          ? ` (${reservation.duree_decoration} j)`
          : ""
      }`,
    );
  }

  return lignes;
}

function ListeDisponibilite() {
  const [reservations, setReservations] = useState([]);
  const [chargement, setChargement] = useState(true);
  const [erreur, setErreur] = useState(null);

  useEffect(() => {
    getDisponibilites()
      .then(setReservations)
      .catch(() => setErreur("Impossible de charger les réservations."))
      .finally(() => setChargement(false));
  }, []);

  if (chargement) return <p className="text-gray-500">Chargement…</p>;
  if (erreur) return <p className="text-red-600">{erreur}</p>;
  if (reservations.length === 0) {
    return <p className="text-gray-500">Aucune réservation enregistrée.</p>;
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-left text-sm border-collapse">
        <thead>
          <tr className="bg-emerald-700 text-white">
            <th className="py-2 px-3 border border-emerald-600">#</th>
            <th className="py-2 px-3 border border-emerald-600">Client</th>
            <th className="py-2 px-3 border border-emerald-600">Prestation</th>
            <th className="py-2 px-3 border border-emerald-600">Dates</th>
            <th className="py-2 px-3 border border-emerald-600">Statut</th>
          </tr>
        </thead>
        <tbody>
          {reservations.map((reservation, index) => {
            const couleur = couleurClient(reservation.client_id);
            return (
              <tr
                key={reservation.id}
                className={index % 2 === 0 ? "bg-gray-50" : "bg-white"}
              >
                <td className="py-2 px-3 border border-gray-200">
                  {reservation.id}
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  <span className="flex items-center gap-2">
                    <span
                      className={`w-2.5 h-2.5 rounded-full ${couleur.pastille}`}
                    />
                    {reservation.client?.nom ?? "—"}
                  </span>
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {LIBELLES_TYPE_PRESTATION[reservation.type_prestation] ??
                    reservation.type_prestation}
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {resumerDates(reservation).map((ligne) => (
                    <div key={ligne}>{ligne}</div>
                  ))}
                </td>
                <td className="py-2 px-3 border border-gray-200">
                  {LIBELLES_STATUT[reservation.statut] ?? reservation.statut}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

export default ListeDisponibilite;

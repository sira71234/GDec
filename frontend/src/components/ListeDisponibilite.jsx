import { useEffect, useState } from "react";
import { getDisponibilites } from "../services/adminService";

const LIBELLES_STATUT = {
  en_attente: "En attente",
  valide: "Validée",
  refuse: "Refusée",
};

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
            <th className="py-2 px-3 border border-emerald-600">Événement</th>
            <th className="py-2 px-3 border border-emerald-600">Début</th>
            <th className="py-2 px-3 border border-emerald-600">Fin</th>
            <th className="py-2 px-3 border border-emerald-600">Statut</th>
          </tr>
        </thead>
        <tbody>
          {reservations.map((reservation, index) => (
            <tr
              key={reservation.id}
              className={index % 2 === 0 ? "bg-gray-50" : "bg-white"}
            >
              <td className="py-2 px-3 border border-gray-200">{reservation.id}</td>
              <td className="py-2 px-3 border border-gray-200">
                {reservation.client?.nom ?? "—"}
              </td>
              <td className="py-2 px-3 border border-gray-200">
                {reservation.type_evenement ?? "—"}
              </td>
              <td className="py-2 px-3 border border-gray-200">
                {reservation.date_debut_location?.slice(0, 10)}
              </td>
              <td className="py-2 px-3 border border-gray-200">
                {reservation.date_fin_location?.slice(0, 10)}
              </td>
              <td className="py-2 px-3 border border-gray-200">
                {LIBELLES_STATUT[reservation.statut] ?? reservation.statut}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default ListeDisponibilite;

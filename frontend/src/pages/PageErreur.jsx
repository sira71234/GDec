import { Link, isRouteErrorResponse, useRouteError } from "react-router-dom";

function Contenu({ titre, message, details = null }) {
  return (
    <main className="min-h-[60vh] flex items-center justify-center px-4 py-16">
      <div className="max-w-lg w-full bg-white border border-gray-200 rounded-xl shadow p-8 text-center">
        <p className="text-5xl font-bold text-emerald-800 mb-3">{titre}</p>
        <h1 className="text-xl font-bold text-gray-800 mb-2">
          Une erreur est survenue
        </h1>
        <p className="text-gray-600 mb-6">{message}</p>
        {details && (
          <p className="text-xs text-gray-400 mb-6 break-words">{details}</p>
        )}
        <div className="flex flex-wrap justify-center gap-3">
          <Link
            to="/"
            className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-5 py-2 rounded-lg"
          >
            Retour à l'accueil
          </Link>
          <Link
            to="/commande"
            className="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-5 py-2 rounded-lg"
          >
            Faire une demande
          </Link>
        </div>
      </div>
    </main>
  );
}

export function PageIntrouvable() {
  return (
    <Contenu
      titre="404"
      message="La page demandée n'existe pas ou a été déplacée."
    />
  );
}

export function PageErreur() {
  const erreur = useRouteError();
  const statut = isRouteErrorResponse(erreur) ? erreur.status : null;

  return (
    <Contenu
      titre={statut ? String(statut) : "Erreur"}
      message={
        statut === 404
          ? "La page demandée est introuvable."
          : "Le site a rencontré un problème inattendu. Veuillez réessayer dans un instant."
      }
      details={
        import.meta.env.DEV && erreur instanceof Error
          ? erreur.message
          : null
      }
    />
  );
}

export default PageErreur;

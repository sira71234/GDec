import { useState } from "react";
import { useNavigate, Navigate, Link } from "react-router-dom";
import { login, isAuthenticated } from "../services/adminService";

function AdminLogin() {
  const navigate = useNavigate();
  const [identifiant, setIdentifiant] = useState("");
  const [motDePasse, setMotDePasse] = useState("");
  const [codeAdmin, setCodeAdmin] = useState("");
  const [erreur, setErreur] = useState(null);
  const [envoi, setEnvoi] = useState(false);

  if (isAuthenticated()) {
    return <Navigate to="/admin/dashboard" replace />;
  }

  const soumettre = async (event) => {
    event.preventDefault();
    setErreur(null);
    setEnvoi(true);
    try {
      await login(identifiant, motDePasse, codeAdmin);
      navigate("/admin/dashboard", { replace: true });
    } catch (error) {
      const statut = error.response?.status;
      const message = error.response?.data?.message;
      setErreur(
        statut === 401 || statut === 422
          ? message || "Connexion refusée. Vérifiez vos identifiants et le code admin."
          : "Connexion impossible. Veuillez réessayer.",
      );
    } finally {
      setEnvoi(false);
    }
  };

  return (
    <div className="min-h-screen bg-emerald-900 flex items-center justify-center px-4">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-8">
        <h1 className="text-2xl font-bold text-emerald-800 text-center mb-1">
          Espace administrateur
        </h1>
        <p className="text-center text-gray-500 text-sm mb-6">
          Accès réservé à l'équipe
        </p>

        <form onSubmit={soumettre} className="grid gap-4">
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">
              Identifiant
            </label>
            <input
              type="text"
              value={identifiant}
              onChange={(e) => setIdentifiant(e.target.value)}
              autoComplete="username"
              required
              className="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">
              Mot de passe
            </label>
            <input
              type="password"
              value={motDePasse}
              onChange={(e) => setMotDePasse(e.target.value)}
              autoComplete="current-password"
              required
              className="w-full border border-gray-300 rounded-lg px-3 py-2"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">
              Code administrateur
            </label>
            <input
              type="password"
              value={codeAdmin}
              onChange={(e) => setCodeAdmin(e.target.value)}
              inputMode="numeric"
              required
              className="w-full border border-gray-300 rounded-lg px-3 py-2 tracking-widest text-center"
            />
          </div>

          {erreur && <p className="text-red-600 text-sm">{erreur}</p>}

          <button
            type="submit"
            disabled={envoi}
            className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-3 rounded-lg transition disabled:opacity-60"
          >
            {envoi ? "Connexion…" : "Se connecter"}
          </button>
        </form>

        <p className="text-center text-sm text-gray-500 mt-6">
          <Link to="/" className="text-emerald-700 hover:underline">
            Retour au site
          </Link>
        </p>
      </div>
    </div>
  );
}

export default AdminLogin;

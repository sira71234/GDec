import { useEffect } from "react";
import { useLocation } from "react-router-dom";
import { ShoppingCart } from "lucide-react";
import SectionLocation from "../components/SectionLocation";
import SectionDecoration from "../components/SectionDecoration";
import PanierRecap from "../components/PanierRecap";
import FormulaireCoordonnees from "../components/FormulaireCoordonnees";
import { usePanier } from "../context/usePanier";

function Commandes() {
  const { nombreArticles } = usePanier();
  const { hash } = useLocation();

  useEffect(() => {
    if (!hash) return;
    const cible = document.querySelector(hash);
    if (cible) {
      cible.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }, [hash]);

  return (
    <div>
      <section className="bg-emerald-800 text-white text-center py-8 px-4">
        <h1 className="text-3xl md:text-5xl font-bold mb-3">
          Demande de location & décoration
        </h1>
        <p className="text-lg max-w-2xl mx-auto">
          Composez votre événement en quelques étapes : choisissez votre matériel,
          votre décoration, puis validez vos coordonnées.
        </p>
        <a
          href="#panier"
          className="relative inline-flex items-center gap-2 mt-5 bg-white text-emerald-900 font-semibold px-5 py-2 rounded-lg"
        >
          <ShoppingCart size={18} />
          Voir mon panier
          {nombreArticles > 0 && (
            <span className="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold rounded-full h-6 min-w-6 px-1 flex items-center justify-center">
              {nombreArticles}
            </span>
          )}
        </a>
      </section>

      <SectionLocation />
      <SectionDecoration />
      <PanierRecap />
      <FormulaireCoordonnees />
    </div>
  );
}

export default Commandes;

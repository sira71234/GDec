import logo from "../assets/logo/logo.jpeg";
import { Link } from "react-router-dom";
import { ShoppingCart } from "lucide-react";
import { usePanier } from "../context/usePanier";

function Header() {
  const { nombreArticles } = usePanier();

  return (
    <header className="bg-emerald-900 shadow-md">
      <div className="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
        <Link to="/" className="flex shrink-0 items-center" aria-label="Accueil">
          <img src={logo} alt="Logo Grace Divine" className="h-10 sm:h-12" />
        </Link>

        <span className="order-3 w-full text-center text-sm font-bold leading-snug text-white sm:order-none sm:w-auto sm:flex-1 sm:px-4 sm:text-base md:text-xl">
          Grace Divine Decor Services &amp; Finnes
        </span>

        <Link
          to="/commande#panier"
          aria-label={`Panier${nombreArticles > 0 ? ` (${nombreArticles} article${nombreArticles > 1 ? "s" : ""})` : ""}`}
          className="relative flex shrink-0 items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-emerald-900 sm:px-4"
        >
          <ShoppingCart size={18} />
          <span className="hidden sm:inline">Panier</span>
          {nombreArticles > 0 && (
            <span className="absolute -right-2 -top-2 flex h-6 min-w-6 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white">
              {nombreArticles}
            </span>
          )}
        </Link>
      </div>
    </header>
  );
}

export default Header;

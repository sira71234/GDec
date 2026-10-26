import logo from "../assets/logo/logo.jpeg";
import { Link } from "react-router-dom";
import { ShoppingCart } from "lucide-react";
import { usePanier } from "../context/usePanier";

function Header() {
  const { nombreArticles } = usePanier();

  return (
    <header className="grid grid-cols-3 items-center p-4 shadow-md bg-emerald-900">
      <Link to="/" className="flex items-center">
        <img src={logo} alt="Logo" className="h-12" />
      </Link>

      <span className="text-xl font-bold text-white text-center">Grace Divine Decor Services & Finnes</span>

      <div className="flex justify-end items-center gap-3">
        <Link
          to="/commande#panier"
          className="relative flex items-center gap-2 bg-white text-emerald-900 font-semibold px-4 py-2 rounded-lg"
        >
          <ShoppingCart size={18} />
          <span className="hidden sm:inline">Panier</span>
          {nombreArticles > 0 && (
            <span className="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold rounded-full h-6 min-w-6 px-1 flex items-center justify-center">
              {nombreArticles}
            </span>
          )}
        </Link>
        <Link to="/commande" className="bg-yellow-500 text-gray-900 font-semibold px-4 py-2 rounded-lg">
          Commander
        </Link>
      </div>
    </header>
  );
}

export default Header;
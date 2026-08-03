import logo from "../assets/logo/logo.jpeg";
import { Link } from "react-router-dom";

function Header() {
  return (
    <header className="grid grid-cols-3 items-center p-4 shadow-md bg-emerald-900">
      <Link to="/" className="flex items-center">
        <img src={logo} alt="Logo" className="h-12" />
      </Link>

      <span className="text-xl font-bold text-white text-center">Grace Divine Decor Services & Finnes</span>

      <div className="flex justify-end">
        <Link to="/commande" className="bg-yellow-500 text-gray-900 font-semibold px-4 py-2 rounded-lg">
          Commander
        </Link>
      </div>
    </header>
  );
}

export default Header;
import logo from "../assets/logo/logo.jpeg";
import { Link } from "react-router-dom";

function Header() {
  return (
    <header className="flex items-center justify-between p-4 shadow-md bg-white">
      <Link to="/" className="flex items-center gap-3">
        <img src={logo} alt="Logo" className="h-12" />
        <span className="text-xl font-bold">Grace Divine Decor Services & Finnes</span>
      </Link>
      <Link to="/commande" className="bg-blue-600 text-white px-4 py-2 rounded-lg">
        Commander
      </Link>
    </header>
  );
}

export default Header;
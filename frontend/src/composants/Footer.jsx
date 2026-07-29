import { MapPin } from "lucide-react";
import { FaInstagram, FaFacebook, FaTiktok, FaWhatsapp } from "react-icons/fa";

function Footer() {
  return (
    <footer className="bg-gray-900 text-white p-6 mt-10">
      <div className="flex flex-col md:flex-row justify-between items-center gap-4">
        <a href="https://wa.me/2290197209259" target="_blank" rel="noreferrer">
            <FaWhatsapp size={20} />
        </a>
        <div className="flex items-center gap-2">
          <MapPin size={18} />
          <a href="LIEN_GOOGLE_MAPS" target="_blank" rel="noreferrer">
            Voir sur la carte
          </a>
        </div>
        <div className="flex items-center gap-4">
          <a href="LIEN_INSTAGRAM" target="_blank" rel="noreferrer">
            <FaInstagram size={20} />
          </a>
          <a href="LIEN_FACEBOOK" target="_blank" rel="noreferrer">
            <FaFacebook size={20} />
          </a>
          <a href="LIEN_TIKTOK" target="_blank" rel="noreferrer">
            <FaTiktok size={20} />
          </a>
        </div>
      </div>
    </footer>
  );
}

export default Footer;
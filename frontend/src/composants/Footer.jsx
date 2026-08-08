import { MapPin } from "lucide-react";
import { FaInstagram, FaFacebook, FaTiktok, FaWhatsapp } from "react-icons/fa";

function Footer() {
  return (
    <footer className="bg-gray-900 text-white p-6 mt-10">
      <div className="flex flex-col md:flex-row justify-between items-center gap-4">
        <a href="https://wa.me/22997209259" target="_blank" rel="noreferrer">
            <FaWhatsapp size={20} />
        </a>
        <div className="flex items-center gap-2">
          <MapPin size={18} />
          <a href="https://www.google.com/maps/dir//GDDSF,+119,+Cotonou/@6.3841092,2.3236804,14z/data=!4m8!4m7!1m0!1m5!1m1!1s0x102355f16c45ef41:0x36c290e31bb806e5!2m2!1d2.4277995!2d6.3562425?entry=ttu&g_ep=EgoyMDI2MDcyOS4wIKXMDSoASAFQAw%3D%3D" target="_blank" rel="noreferrer">
            Voir sur la carte
          </a>
        </div>
        <div className="flex items-center gap-4">
          <a href="https://www.instagram.com/gddsfiness?igsh=MWd5ODJlaHoyMjZxOQ==" target="_blank" rel="noreferrer">
            <FaInstagram size={20} />
          </a>
          <a href="https://www.facebook.com/gddsfiness" target="_blank" rel="noreferrer">
            <FaFacebook size={20} />
          </a>
          <a href="https://vm.tiktok.com/ZS9hykuPrYsGw-Q9ScB/" target="_blank" rel="noreferrer">
            <FaTiktok size={20} />
          </a>
        </div>
      </div>
    </footer>
  );
}

export default Footer;
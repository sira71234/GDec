import { useCallback, useEffect, useRef, useState } from "react";
import { storageUrl } from "../services/api";

/**
 * Sélection d'une photo : import de fichier classique ou capture directe via la
 * caméra de l'appareil (getUserMedia). Le composant ne gère que le fichier choisi ;
 * l'aperçu d'un enregistrement existant est passé via `photoExistante`.
 *
 * @param {{
 *   label?: string,
 *   fichier: File|null,
 *   onFichier: (fichier: File|null) => void,
 *   photoExistante?: string|null,
 *   requis?: boolean,
 * }} props
 */
function PhotoUploader({ label = "Photo", fichier, onFichier, photoExistante, requis = false }) {
  const [apercu, setApercu] = useState(null);
  const [cameraOuverte, setCameraOuverte] = useState(false);
  const [erreurCamera, setErreurCamera] = useState(null);
  const videoRef = useRef(null);
  const fluxRef = useRef(null);
  const inputRef = useRef(null);

  // Construit l'aperçu : fichier nouvellement choisi, sinon photo enregistrée.
  useEffect(() => {
    if (fichier) {
      const url = URL.createObjectURL(fichier);
      setApercu(url);
      return () => URL.revokeObjectURL(url);
    }
    setApercu(photoExistante ? storageUrl(photoExistante) : null);
  }, [fichier, photoExistante]);

  const arreterCamera = useCallback(() => {
    if (fluxRef.current) {
      fluxRef.current.getTracks().forEach((piste) => piste.stop());
      fluxRef.current = null;
    }
  }, []);

  // Coupe la caméra au démontage du composant.
  useEffect(() => arreterCamera, [arreterCamera]);

  const demarrerCamera = async () => {
    setErreurCamera(null);
    try {
      const flux = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: "environment" },
        audio: false,
      });
      fluxRef.current = flux;
      setCameraOuverte(true);
      // Le <video> n'existe qu'après le rendu provoqué par setCameraOuverte.
      requestAnimationFrame(() => {
        if (videoRef.current) {
          videoRef.current.srcObject = flux;
        }
      });
    } catch {
      setErreurCamera(
        "Caméra indisponible. Utilisez l'import de fichier ci-dessous.",
      );
    }
  };

  const fermerCamera = () => {
    arreterCamera();
    setCameraOuverte(false);
  };

  const capturer = () => {
    const video = videoRef.current;
    if (!video) {
      return;
    }
    const canvas = document.createElement("canvas");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);
    canvas.toBlob(
      (blob) => {
        if (blob) {
          onFichier(new File([blob], `photo-${Date.now()}.jpg`, { type: "image/jpeg" }));
        }
        fermerCamera();
      },
      "image/jpeg",
      0.9,
    );
  };

  return (
    <div className="space-y-2">
      <label className="block text-sm font-semibold text-gray-700">
        {label}
        {requis && <span className="text-red-500"> *</span>}
      </label>

      {apercu && (
        <img
          src={apercu}
          alt="Aperçu"
          className="h-40 w-full rounded-lg object-cover border border-gray-200"
        />
      )}

      {cameraOuverte ? (
        <div className="space-y-2">
          <video
            ref={videoRef}
            autoPlay
            playsInline
            muted
            className="h-48 w-full rounded-lg bg-black object-cover"
          />
          <div className="flex gap-2">
            <button
              type="button"
              onClick={capturer}
              className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-lg"
            >
              Capturer
            </button>
            <button
              type="button"
              onClick={fermerCamera}
              className="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-4 py-2 rounded-lg"
            >
              Annuler
            </button>
          </div>
        </div>
      ) : (
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={demarrerCamera}
            className="bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-semibold px-4 py-2 rounded-lg"
          >
            Prendre une photo
          </button>
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            className="bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold px-4 py-2 rounded-lg"
          >
            Choisir un fichier
          </button>
          {apercu && (
            <button
              type="button"
              onClick={() => {
                onFichier(null);
                if (inputRef.current) {
                  inputRef.current.value = "";
                }
              }}
              className="text-red-600 hover:underline text-sm font-semibold"
            >
              Retirer
            </button>
          )}
        </div>
      )}

      <input
        ref={inputRef}
        type="file"
        accept="image/*"
        capture="environment"
        className="hidden"
        onChange={(event) => onFichier(event.target.files?.[0] ?? null)}
      />

      {erreurCamera && <p className="text-sm text-red-600">{erreurCamera}</p>}
    </div>
  );
}

export default PhotoUploader;

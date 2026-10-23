// Palette déterministe : un même client garde toujours la même couleur,
// ce qui permet de le reconnaître d'un coup d'œil dans le calendrier.
const PALETTE = [
  {
    fond: "bg-rose-100",
    bord: "border-rose-400",
    texte: "text-rose-800",
    pastille: "bg-rose-500",
  },
  {
    fond: "bg-sky-100",
    bord: "border-sky-400",
    texte: "text-sky-800",
    pastille: "bg-sky-500",
  },
  {
    fond: "bg-amber-100",
    bord: "border-amber-400",
    texte: "text-amber-800",
    pastille: "bg-amber-500",
  },
  {
    fond: "bg-violet-100",
    bord: "border-violet-400",
    texte: "text-violet-800",
    pastille: "bg-violet-500",
  },
  {
    fond: "bg-emerald-100",
    bord: "border-emerald-400",
    texte: "text-emerald-800",
    pastille: "bg-emerald-500",
  },
  {
    fond: "bg-orange-100",
    bord: "border-orange-400",
    texte: "text-orange-800",
    pastille: "bg-orange-500",
  },
  {
    fond: "bg-cyan-100",
    bord: "border-cyan-400",
    texte: "text-cyan-800",
    pastille: "bg-cyan-500",
  },
  {
    fond: "bg-fuchsia-100",
    bord: "border-fuchsia-400",
    texte: "text-fuchsia-800",
    pastille: "bg-fuchsia-500",
  },
  {
    fond: "bg-lime-100",
    bord: "border-lime-400",
    texte: "text-lime-800",
    pastille: "bg-lime-500",
  },
  {
    fond: "bg-indigo-100",
    bord: "border-indigo-400",
    texte: "text-indigo-800",
    pastille: "bg-indigo-500",
  },
];

export function couleurClient(clientId) {
  const identifiant = Number(clientId) || 0;
  return PALETTE[Math.abs(identifiant) % PALETTE.length];
}

export const LIBELLES_TYPE_PRESTATION = {
  location: "Location",
  decoration: "Décoration",
  les_deux: "Location + Décoration",
};

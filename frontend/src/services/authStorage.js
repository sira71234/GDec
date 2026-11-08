// Stockage de la session administrateur.
// On garde aussi une copie en mémoire et dans sessionStorage : certains
// navigateurs / contextes bloquent localStorage sans lever d'erreur visible.

const CLE_JETON = "admin_token";
const CLE_ADMIN = "admin";

let jetonMemoire = null;
let adminMemoire = null;

function lireStockage(stockage, cle) {
  try {
    return stockage?.getItem(cle) ?? null;
  } catch {
    return null;
  }
}

function ecrireStockage(stockage, cle, valeur) {
  try {
    stockage?.setItem(cle, valeur);
  } catch {
    // Le stockage est indisponible : la copie mémoire prend le relais.
  }
}

function supprimerStockage(stockage, cle) {
  try {
    stockage?.removeItem(cle);
  } catch {
    // Rien à faire.
  }
}

export function enregistrerSession(jeton, admin) {
  jetonMemoire = jeton;
  adminMemoire = admin ?? null;

  const adminTexte = admin ? JSON.stringify(admin) : null;

  ecrireStockage(localStorage, CLE_JETON, jeton);
  ecrireStockage(sessionStorage, CLE_JETON, jeton);
  if (adminTexte) {
    ecrireStockage(localStorage, CLE_ADMIN, adminTexte);
    ecrireStockage(sessionStorage, CLE_ADMIN, adminTexte);
  }
}

export function lireJeton() {
  if (jetonMemoire) return jetonMemoire;

  const jeton =
    lireStockage(localStorage, CLE_JETON) ||
    lireStockage(sessionStorage, CLE_JETON);

  if (jeton) jetonMemoire = jeton;
  return jeton;
}

export function lireAdmin() {
  if (adminMemoire) return adminMemoire;

  const brut =
    lireStockage(localStorage, CLE_ADMIN) ||
    lireStockage(sessionStorage, CLE_ADMIN);

  if (!brut) return null;

  try {
    adminMemoire = JSON.parse(brut);
    return adminMemoire;
  } catch {
    return null;
  }
}

export function effacerSession() {
  jetonMemoire = null;
  adminMemoire = null;
  supprimerStockage(localStorage, CLE_JETON);
  supprimerStockage(sessionStorage, CLE_JETON);
  supprimerStockage(localStorage, CLE_ADMIN);
  supprimerStockage(sessionStorage, CLE_ADMIN);
}

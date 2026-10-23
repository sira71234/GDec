import { usePanier } from "../context/usePanier";

function Ligne({ nom, quantite, prix, onQuantiteChange, onRemove }) {
  return (
    <div className="flex items-center justify-between gap-3 py-2 border-b border-gray-100">
      <span className="grow text-gray-800">{nom}</span>
      {onQuantiteChange && (
        <input
          type="number"
          min="1"
          value={quantite}
          onChange={(e) => onQuantiteChange(Number(e.target.value))}
          className="w-16 border border-gray-300 rounded-md px-2 py-1 text-center"
        />
      )}
      <span className="w-28 text-right font-semibold text-gray-700">
        {Number(prix).toLocaleString("fr-FR")} FCFA
      </span>
      <button
        type="button"
        onClick={onRemove}
        className="text-red-500 hover:text-red-700 text-sm"
        aria-label="Retirer"
      >
        Retirer
      </button>
    </div>
  );
}

function PanierRecap() {
  const {
    materiels,
    prestations,
    elements,
    decoration,
    updateQuantiteMateriel,
    removeMateriel,
    updateQuantitePrestation,
    removePrestation,
    toggleElement,
    totalMateriel,
    totalPrestation,
    totalGeneral,
    cautionEstimee,
    clear,
  } = usePanier();

  const vide =
    materiels.length === 0 && prestations.length === 0 && elements.length === 0;

  return (
    <section className="max-w-4xl mx-auto my-8 p-6 bg-white rounded-xl shadow border border-emerald-100">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-xl font-bold text-emerald-800">Récapitulatif</h2>
        {!vide && (
          <button
            type="button"
            onClick={clear}
            className="text-sm text-gray-500 hover:text-red-600"
          >
            Tout vider
          </button>
        )}
      </div>

      {vide ? (
        <p className="text-gray-500">
          Votre sélection est vide. Ajoutez du matériel ou des prestations de décoration.
        </p>
      ) : (
        <>
          {materiels.length > 0 && (
            <div className="mb-6">
              <h3 className="font-semibold text-gray-600 mb-2">Matériel loué</h3>
              {materiels.map((item) => (
                <Ligne
                  key={item.id}
                  nom={item.nom}
                  quantite={item.quantite}
                  prix={Number(item.prix_unitaire) * item.quantite}
                  onQuantiteChange={(q) => updateQuantiteMateriel(item.id, q)}
                  onRemove={() => removeMateriel(item.id)}
                />
              ))}
              <p className="text-right text-sm text-gray-600 mt-2">
                Sous-total matériel :{" "}
                <strong>{Number(totalMateriel).toLocaleString("fr-FR")} FCFA</strong>
              </p>
            </div>
          )}

          {prestations.length > 0 && (
            <div className="mb-6">
              <h3 className="font-semibold text-gray-600 mb-2">
                Prestations de décoration
              </h3>
              {prestations.map((item) => (
                <Ligne
                  key={item.id}
                  nom={item.nom}
                  quantite={item.quantite}
                  prix={Number(item.prix) * item.quantite}
                  onQuantiteChange={(q) => updateQuantitePrestation(item.id, q)}
                  onRemove={() => removePrestation(item.id)}
                />
              ))}
              <p className="text-right text-sm text-gray-600 mt-2">
                Sous-total décoration :{" "}
                <strong>{Number(totalPrestation).toLocaleString("fr-FR")} FCFA</strong>
              </p>
            </div>
          )}

          {elements.length > 0 && (
            <div className="mb-6">
              <h3 className="font-semibold text-gray-600 mb-2">
                Éléments à décorer
              </h3>
              <div className="flex flex-wrap gap-2">
                {elements.map((element) => (
                  <span
                    key={element.id}
                    className="inline-flex items-center gap-2 bg-emerald-50 text-emerald-800 px-3 py-1 rounded-full text-sm"
                  >
                    {element.nom}
                    <button
                      type="button"
                      onClick={() => toggleElement(element)}
                      className="text-emerald-600 hover:text-red-600"
                      aria-label="Retirer"
                    >
                      ×
                    </button>
                  </span>
                ))}
              </div>
            </div>
          )}

          {(decoration.type_evenement ||
            decoration.date_evenement ||
            decoration.theme ||
            decoration.couleurs) && (
            <div className="mb-6 bg-emerald-50 rounded-lg p-4">
              <h3 className="font-semibold text-emerald-800 mb-2">
                Détails de la décoration
              </h3>
              <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-sm text-gray-700">
                {decoration.type_evenement && (
                  <div>
                    <dt className="inline font-semibold">Événement : </dt>
                    <dd className="inline">{decoration.type_evenement}</dd>
                  </div>
                )}
                {decoration.date_evenement && (
                  <div>
                    <dt className="inline font-semibold">Date : </dt>
                    <dd className="inline">{decoration.date_evenement}</dd>
                  </div>
                )}
                {decoration.duree_decoration && (
                  <div>
                    <dt className="inline font-semibold">Durée : </dt>
                    <dd className="inline">{decoration.duree_decoration} jour(s)</dd>
                  </div>
                )}
                {decoration.nombre_personnes && (
                  <div>
                    <dt className="inline font-semibold">Invités : </dt>
                    <dd className="inline">{decoration.nombre_personnes}</dd>
                  </div>
                )}
                {decoration.theme && (
                  <div>
                    <dt className="inline font-semibold">Thème : </dt>
                    <dd className="inline">{decoration.theme}</dd>
                  </div>
                )}
                {decoration.couleurs && (
                  <div>
                    <dt className="inline font-semibold">Couleurs : </dt>
                    <dd className="inline">{decoration.couleurs}</dd>
                  </div>
                )}
              </dl>
            </div>
          )}

          <div className="flex flex-col items-end gap-1 border-t border-gray-200 pt-4">
            <p className="text-gray-700">
              Total :{" "}
              <strong className="text-lg text-emerald-800">
                {Number(totalGeneral).toLocaleString("fr-FR")} FCFA
              </strong>
            </p>
            {cautionEstimee > 0 && (
              <p className="text-sm text-gray-500">
                Caution estimée (30 % du matériel) :{" "}
                {Number(cautionEstimee).toLocaleString("fr-FR")} FCFA
              </p>
            )}
          </div>
        </>
      )}
    </section>
  );
}

export default PanierRecap;

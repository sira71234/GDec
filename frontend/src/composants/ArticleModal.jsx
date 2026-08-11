function ArticleModal({article, quantite, onQuantiteChange, onClose, onValider}) {
    if (!article) {
  return null;
    }

    return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center">
        <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <img src={article.photo} alt={article.nom} className="w-full h-auto" />
            <p className="text-center mt-auto pt-3">{article.nom}</p>
            {article.prix_unitaire && <p className="text-lg font-bold text-green-500 text-center">{article.prix_unitaire} FCFA</p>}
            {/*Quantité*/}
            <div className="flex items-center justify-center gap-3 mt-4">
                <label htmlFor="quantite" className="font-semibold text-gray-700">
                    Quantité:
                </label>
                <input
                type="number"
                value={quantite}
                onChange={(e) => onQuantiteChange(Number(e.target.value))}
                min="1"
                className="w-16 text-center border rounded"
                />
            </div>
            <div className="flex items-center justify-center gap-3 mt-4">
                <button className="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg" onClick={onClose}>Annuler</button>
                <button className="bg-yellow-500 text-gray-900 font-semibold px-4 py-2 rounded-lg" onClick={onValider}>Ajouter au panier</button>
            </div>
        </div>
    </div>
    );
}

export default ArticleModal;
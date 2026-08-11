function Panier({panier, panierOuvert, onToggle}) {
    const totalArticles = panier.reduce((acc, article) => acc + article.quantite, 0);
    return (
        <div className="w-full bg-emerald-800 text-white shadow-lg">
            <div className="flex justify-between items-center px-6 py-3 cursor-pointer" onClick={onToggle}>
            {panierOuvert && (
            <div className="w-full bg-white text-gray-800 p-6">
                <h3 className="text-xl font-bold text-emerald-800 mb-4">Mon panier</h3>
                <table className="w-full text-left border border-gray-300 border-collapse">
              <thead>
                    <tr className="bg-emerald-700 text-white">
                        <th className="py-3 px-4 border border-gray-300">Article</th>
                        <th className="py-3 px-4 border border-gray-300 text-center">Quantité</th>
                        <th className="py-3 px-4 border border-gray-300 text-right">Prix unitaire</th>
                        <th className="py-3 px-4 border border-gray-300 text-right">Sous-total</th>
                        <th className="py-3 px-4 border border-gray-300 text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    {panier.map((article, index) => (
                        <tr key={index} className={index % 2 === 0 ? "bg-gray-50" : "bg-white"}>
                        <td className="py-2 px-4 border border-gray-300">{article.nom}</td>
                        <td className="py-2 px-4 border border-gray-300 text-center">{article.quantite}</td>
                        <td className="py-2 px-4 border border-gray-300 text-right">{article.prix_unitaire} FCFA</td>
                        <td className="py-2 px-4 border border-gray-300 text-right font-semibold">{article.prix_unitaire * article.quantite} FCFA</td>
                        <td className="py-2 px-4 border border-gray-300 text-center">
                            {/* boutons à ajouter juste après */}
                        </td>
                        </tr>
                    ))}
                    </tbody>
                </table>
            </div>
            )}
            <span className="font-semibold">
                Mon panier ({totalArticles} article{totalArticles > 1 ? "s" : ""})
            </span>
            <span>{panierOuvert ? "▲" : "▼"}</span>
            </div>
        </div>
    );
}

export default Panier;
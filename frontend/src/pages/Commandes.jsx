import dish_circulaire from "../assets/images/dish_circulaire.jpg";
import verre_a_eau from "../assets/images/verre_a_eau.jpg";
import verre_a_vin from "../assets/images/verre_a_vin.jpg";
import ArticleCards from "../composants/ArticleCards.jsx";
import intérieurs_élegants from "../assets/images/Intérieurs_élégants.jpeg";
import Grâce_Déco from "../assets/images/Grâce_Déco.jpeg";
import { useState } from "react";
import ArticleModal from "../composants/ArticleModal.jsx";
import Panier from "../composants/Panier.jsx";

const articles_location = [
    {nom: "Dish", photo: dish_circulaire, prix_unitaire: 10},
    {nom: "verre a eau", photo: verre_a_eau, prix_unitaire: 5},
    {nom: "verre a vin", photo: verre_a_vin, prix_unitaire: 7},
]

const articles_decoration = [
    {nom: "Intérieurs élégants", photo: intérieurs_élegants, prix_unitaire: 15},
    {nom: "Grâce Déco", photo: Grâce_Déco, prix_unitaire: 20},
]

function Commandes() {
    const [articleSelectionne, setArticleSelectionne] = useState(null);
    const [quantite, setQuantite] = useState(1);
    const [panier, setPanier] = useState([]);
    const [panierOuvert, setPanierOuvert] = useState(false);
    return (
        <div>
            {/*section de presentation*/}
            <section className="bg-emerald-800 text-white text-center py-8 px-4">
                <h1 className="text-3xl md:text-5xl font-bold mb-4">
                    Catalogue de produits
                </h1>
            </section>

            {/*section location*/}
            <section className="bg-white py-8 border-t-4 border-emerald-700 border-emerald-100">
                <div className="text-emerald-800 text-center py-2 px-4">
                    <h2 className="text-2xl md:text-3xl font-bold mb-4">
                        Location
                    </h2>
                    <p className="text-lg mb-6 max-w-2xl mx-auto">
                        Ajouter vos articles à votre panier et passer votre commande en toute simplicité.
                    </p>
                </div>
                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-6">
                    {articles_location.map((article) => (
                        <ArticleCards key={article.nom} nom={article.nom} photo={article.photo} prix_unitaire={article.prix_unitaire} onClick={() => setArticleSelectionne(article)} />
                    ))}
                </div>
            </section>

            {/*section decoration*/}
            <section className="bg-emerald-50 py-8 border-t-4 border-emerald-700 border-emerald-100">
                <div className="text-emerald-800 text-center py-2 px-4">
                    <h2 className="text-2xl md:text-3xl font-bold mb-4">
                        Décoration
                    </h2>
                    <p className="text-lg mb-6 max-w-2xl mx-auto">
                        Composez votre décoration en choisissant parmi nos models pour sublimer vos événements.
                    </p>
                </div>
                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-6">
                    {articles_decoration.map((article) => (
                        <ArticleCards key={article.nom} nom={article.nom} photo={article.photo} prix_unitaire={article.prix_unitaire} />
                    ))}
                </div>
            </section>
            
            {/*Modal*/}
            <ArticleModal
            article={articleSelectionne}
            quantite={quantite}
            onQuantiteChange={setQuantite}
            onClose={() => setArticleSelectionne(null)}
            onValider={() => {
                const nouvelArticle = { ...articleSelectionne, quantite: quantite };
                setPanier([...panier, nouvelArticle]);
                setArticleSelectionne(null);
            }}
            />

            {/*Panier*/}
            <Panier
            panier={panier}
            panierOuvert={panierOuvert}
            onToggle={() => setPanierOuvert(!panierOuvert)}
            />
        </div>
    );
};

export default Commandes;


   
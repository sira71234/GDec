import { Link } from "react-router-dom";
import ArticleCard from "../composants/ArticleCards.jsx";
const images = import.meta.glob("../assets/images/*.{jpg,jpeg,png,webp}", { eager: true });


function Home() {
    const articles = Object.entries(images).map(([chemin, module]) => {
    const nomFichier = chemin.split("/").pop().split(".")[0];
    const nom = nomFichier.replace(/_/g, " ");
    return { photo: module.default, nom };
    });

    return (
    <>
        <section className="bg-emerald-800 text-white text-center py-16 px-4">
        <h1 className="text-3xl md:text-5xl font-bold mb-4">
            Location & Décoration pour tous vos événements
        </h1>
        <p className="text-lg mb-6 max-w-2xl mx-auto">
            Des solutions clé en main pour réussir votre mariage, anniversaire, ou événement d'entreprise.
        </p>
        <Link to="/commande" className="bg-yellow-500 text-gray-900 font-semibold px-6 py-3 rounded-lg inline-block">
            Commander maintenant
        </Link>
        </section>

        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-6">
        {articles.map((article) => (
            <ArticleCard key={article.photo} photo={article.photo} nom={article.nom} />
        ))}
        </div>
    </>
    );
};

export default Home;
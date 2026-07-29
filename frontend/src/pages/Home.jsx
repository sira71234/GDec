import ArticleCard from "../composants/ArticleCards.jsx";
const images = import.meta.glob("../assets/images/*.jpg", { eager: true });


function Home() {
    const ImageList = Object.values(images).map((image) => image.default);

    return (
        <div className="grid grid-cols-3 gap-4 p-6">
            {ImageList.map((url) => (
            <ArticleCard key={url} photo={url} nom="Article" />
            ))}
        </div>
    );
};

export default Home;
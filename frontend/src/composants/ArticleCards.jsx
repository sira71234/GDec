function ArticleCard({ photo, nom }) {
  return (
    <div className="rounded-lg shadow-md p-3 overflow-hidden flex flex-col h-full hover:scale-105 transition">
      <img src={photo} alt={nom} className="w-full h-auto" />
      <p className="text-center mt-auto pt-3">{nom}</p>
    </div>
  );
}

export default ArticleCard;
function ArticleCard({ photo, nom }) {
  return (
    <div className="rounded-lg shadow-md overflow-hidden w-full p-3">
      <img src={photo} alt={nom} />
      <p className="text-center">{nom}</p>
    </div>
  );
}

export default ArticleCard;
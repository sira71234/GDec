function ArticleCards({ photo, nom, prix_unitaire, onClick }) {
  return (
    <div className="rounded-lg shadow-md p-3 overflow-hidden flex flex-col h-full hover:scale-105 transition cursor-pointer" onClick={onClick}>
      <div className="w-full aspect-[4/3] overflow-hidden rounded bg-gray-100">
        <img src={photo} alt={nom} className="w-full h-full object-cover" loading="lazy" />
      </div>
      <p className="text-center mt-auto pt-3">{nom}</p>
      {prix_unitaire && <p className="text-lg font-bold text-green-500 text-center">{prix_unitaire} FCFA</p>}

    </div>
  );
}

export default ArticleCards;
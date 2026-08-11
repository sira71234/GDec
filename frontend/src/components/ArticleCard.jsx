import { useState } from "react";

function ArticleCard({ nom, photo, prix, description  }) {
    const [quantite, setQuantite] = useState(0);
    return (
  <div className="flex gap-4 p-4 border border-gray-200 rounded-xl bg-white">
        <img className="w-24 h-24 shrink-0 object-cover rounded-lg" src={photo} alt={nom} /> 
        <div className="flex flex-col gap-1">
            <h3 className="font-semibold text-lg">{nom}</h3>
            {description && <p className="text-sm text-gray-500">{description}</p>}
            <p className="text-green-600 font-semibold"> {prix} FCFA</p>
            <input className="w-16 border border-gray-300 rounded-md px-2 py-1" type="number" value={quantite} onChange={(event) =>
                 setQuantite(Number(event.target.value))}  />
  </div>
  </div>
  
);

};
export default ArticleCard
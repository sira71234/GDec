import SectionLocation from "../components/SectionLocation";
import SectionDecoration from "../components/SectionDecoration";
import PanierRecap from "../components/PanierRecap";
import FormulaireCoordonnees from "../components/FormulaireCoordonnees";

function Commandes() {
  return (
    <div>
      <section className="bg-emerald-800 text-white text-center py-8 px-4">
        <h1 className="text-3xl md:text-5xl font-bold mb-3">
          Demande de location & décoration
        </h1>
        <p className="text-lg max-w-2xl mx-auto">
          Composez votre événement en quelques étapes : choisissez votre matériel,
          votre décoration, puis validez vos coordonnées.
        </p>
      </section>

      <SectionLocation />
      <SectionDecoration />
      <PanierRecap />
      <FormulaireCoordonnees />
    </div>
  );
}

export default Commandes;

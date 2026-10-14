# Revue d'architecture — Grace Divine Decor (GDec)

> Regard d'un développeur senior (back + front) qui découvre le projet.
> Objectif : pas une liste de bonnes pratiques génériques, mais un diagnostic
> **ancré dans le code réel** de ce dépôt, avec des propositions activables.
>
> Pile observée : Laravel 13 / PHP 8.5, React 19 / Vite / Tailwind v4, Sanctum, MySQL, dompdf, Pest.
> Date de la revue : avril 2026.

---

## 0. Verdict en une page

Le projet est **au-dessus de la moyenne des projets d'équipe étudiante** : modèle
relationnel cohérent, migrations commentées, soft-deletes réfléchis, tests Pest,
seeder de catalogue en `updateOrCreate`, PDF + mail déjà branchés, et maintenant une
auth admin, un formulaire de commande et un dashboard fonctionnels.

Ce qui **bloque une mise en production sereine** n'est pas le manque de features, c'est
la **profondeur de quelques décisions structurantes** :

1. **Le stock n'est jamais décrémenté et la disponibilité n'est pas calculée par
   article.** C'est LE bug métier silencieux : on peut louer 300 chaises sur 10 en
   stock, et valider un créneau que la matière ne permet pas. (`DisponibiliteController`
   teste l'existence *globale* d'une commande sur la plage, `quantite_stock` n'est lu
   que pour l'affichage.)
2. **Authentification admin par token en `localStorage`**, alors que la doc Sanctum
   déconseille explicitement ce choix pour une SPA first-party (XSS ⇒ vol de jeton),
   et `sanctum.expiration = null` (jeton éternel).
3. **Les pièces d'identité (données personnelles) sont stockées sur le disque
   `public`** : `$request->file(...)->store('pieces_identite', 'public')`. N'importe
   quelle URL `/storage/pieces_identite/...` est lisible sans auth.
4. **La logique métier vit dans les contrôleurs** (transaction, PDF, mail dans
   `CommandeController@store`) et le JSON est sérialisé en renvoyant les modèles
   Eloquent bruts → casing instable (`prestations_decoration`), fuite de champs
   internes, pas de contrat d'API.
5. **Front** : chaque écran rejoue `useEffect + useState(chargement/erreur)` à la
   main, deux dossiers de composants coexistent (`composants/` et `components/`),
   pas de couche de cache/mutation, pas de TypeScript.

Le reste du document détaille chacune avec du code. La feuille de route §9 classe
tout par priorité et par coût.

---

## 1. Périmètre de la revue

Revu : `routes/api.php`, les contrôleurs `Api/*`, `Services/*`, `Models/*`,
migrations, `tests/Feature/*`, la couche `frontend/src` (pages, composants, services,
contexte), la config auth/sanctum/CORS.

Non revu (hors périmètre / pas de code) : déploiement, CI réelle, plan de reprise,
DNS/HTTPS, dimensionnement MySQL.

---

## 2. Ce qui est déjà solide (à ne pas casser)

- **Le schéma relationnel** suit le dictionnaire des données à la lettre, avec des
  clés de pivot explicites (`prestation_decoration_id`) — beaucoup de devs se
  plantent là.
- **`Commande` en `SoftDeletes`** + corbeille admin : bon réflexe métier (litiges).
- **Casts** corrects (`decimal:2`, `date`, `integer`) — évite les bugs de type.
- **`Admin` avec casts `hashed`** sur `mot_de_passe` ET `code_admin` : le double
  secret est haché, bien.
- **Séparation client/admin** : pas de `User` réutilisé à tort, c'est lisible.
- **Tests Pest** qui couvrent les scénarios réels (chevauchement de créneaux,
  caution, auth). La culture de test est présente, c'est rare.
- **Seeder idempotent** (`updateOrCreate`) : rejouable sans doublons.

---

## 3. Back-end

### 3.1 Sortir l'orchestration des contrôleurs → Pattern Action + DTO

Aujourd'hui `CommandeController@store` fait : validation → transaction → création
client → upload → création commande → pivots → calcul caution → save → PDF → mail.
C'est **six responsabilités** dans une méthode. Le jour où l'on veut créer une
commande depuis un import admin, un webhook ou une commande console, on duplique.

Proposition : un **Action** (une opération métier = une classe) qui possède la
transaction, et des **Services** purs pour le calcul. Règle simple et non négociable :
`Controller → Action → Services`, et un Service n'appelle jamais un Action.

`app/Actions/Commande/CreerCommandeAction.php`

```php
<?php

declare(strict_types=1);

namespace App\Actions\Commande;

use App\DataTransferObjects\CreerCommandeData;
use App\Enums\StatutCommande;
use App\Enums\TypePrestation;
use App\Mail\CommandeConfirmationMail;
use App\Models\Commande;
use App\Services\Caution\CalculateurCaution;
use App\Services\Pdf\GenerateurPdfCommande;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

final readonly class CreerCommandeAction
{
    public function __construct(
        private CalculateurCaution $calculateur,
        private GenerateurPdfCommande $pdf,
    ) {}

    public function executer(CreerCommandeData $data): Commande
    {
        $commande = DB::transaction(function () use ($data): Commande {
            $pieceIdentite = null;
            if ($data->pieceIdentitePhoto !== null) {
                // DISQUE PRIVÉ : donnée personnelle (voir §4.4).
                $pieceIdentite = $data->pieceIdentitePhoto->store('pieces_identite', 'prive');
            }

            $commande = Commande::create([
                ...$data->commandeAttributes(),
                'piece_identite_photo' => $pieceIdentite,
                'statut' => StatutCommande::EnAttente,
            ]);

            $commande->materiels()->attach($data->materiels);
            $commande->prestationsDecoration()->attach($data->prestations);
            $commande->elementsDecor()->attach($data->elementsDecor);

            $commande->montant_caution = $this->calculateur->pour($commande);
            $commande->montant_total = $this->calculateur->total($commande);
            $commande->reference = $this->calculateur->reference(); // §4.2
            $commande->save();

            return $commande;
        });

        // Post-traitements APRÈS commit, et via la queue (voir §3.5).
        $this->pdf->genererRecapitulatif($commande);

        return $commande;
    }
}
```

`app/DataTransferObjects/CreerCommandeData.php` (readonly, pas de logique) :

```php
final readonly class CreerCommandeData
{
    public function __construct(
        public string $nom,
        public string $telephone,
        public ?string $email = null,
        public ?string $adresse = null,
        public TypePrestation $type = TypePrestation::Location,
        public ?string $dateDebut = null,
        public ?string $dateFin = null,
        public ?UploadedFile $pieceIdentitePhoto = null,
        public array $materiels = [],       // [ ['id' => int, 'quantite' => int], ... ]
        public array $prestations = [],
        public array $elementsDecor = [],
    ) {}

    public function commandeAttributes(): array { /* ... */ }
}
```

Le `StoreCommandeRequest::toData()` construit le DTO — le contrôleur ne touche plus
jamais `$request->validated()` directement.

**Bénéfice concret pour cette équipe** : Kevin (back) peut tester l'Action en isolation
sans HTTP, Siracide/Véronique (front) obtiennent un contrat stable, Esdras peut
brancher une seconde porte d'entrée (import) sans toucher au métier.

---

### 3.2 Remplacer les chaînes magiques par des Enums et un Value Object `Money`

On trouve `'location'`, `'en_attente'`, etc. **en dur** dans les contrôleurs, les
validateurs, les migrations et le front. Une faute de frappe = bug silencieux non
détecté par le typage.

```php
enum TypePrestation: string
{
    case Location = 'location';
    case Decoration = 'decoration';
    case LesDeux = 'les_deux';

    public function inclutLocation(): bool
    {
        return $this !== self::Decoration;
    }
}

enum StatutCommande: string
{
    case EnAttente = 'en_attente';
    case Valide = 'valide';
    case Refuse = 'refuse';

    public function peutTransitionnerVers(self $cible): bool
    {
        return match ($this) {
            self::EnAttente => in_array($cible, [self::Valide, self::Refuse], true),
            self::Valide, self::Refuse => false, // états terminaux
        };
    }
}
```

Et côté argent : manipuler des `float` pour des montants en FCFA est une dette. En
FCFA il n'y a pas de centimes, donc un **entier** suffit — et c'est exact :

```php
final readonly class Money
{
    private function __construct(public int $montantFcfa) {}

    public static function fcfa(int $montant): self
    {
        return new self($montant);
    }

    public function ajouter(self $autre): self { return new self($this->montantFcfa + $autre->montantFcfa); }
    public function pourcentage(int $taux): self { return new self((int) round($this->montantFcfa * $taux / 100)); }
    public function format(): string { return number_format($this->montantFcfa, 0, ',', ' ').' FCFA'; }
}
```

Le `CautionCalculatorService` actuel devient `CalculateurCaution` retournant `Money`,
avec le taux lu depuis `config/decor.php` (`'taux_caution' => env('TAUX_CAUTION', 30)`)
au lieu d'une constante codée dans la classe.

**Point d'attention réel** : `montant_total` n'est **pas persisté** aujourd'hui, seule
la caution l'est. On le recalcule côté devis. Si les prix changent, un vieux devis ne
se recalcule plus à l'identique. **Il faut figer le montant sur la commande.**

---

### 3.3 Arrêter de renvoyer les modèles Eloquent bruts → API Resources

`AdminCommandeController@show` fait `response()->json($commande)` : on expose alors
`piece_identite_photo`, les timestamps, `deleted_at`, et le casing dépend de
`snakeAttributes`. Résultat : le front doit deviner si c'est `prestations_decoration`
ou `prestationsDecoration` — fragile.

Laravel 13 apporte **deux** options, à choisir une fois pour toutes :

- **`JsonResource` classique** (le plus simple ici) ;
- **JSON:API resources** (`php artisan make:resource CommandeResource --json-api`),
  pertinent si un client mobile/tiers doit consommer l'API plus tard — ce qui est
  plausible pour ce projet.

```php
final class CommandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'type_prestation' => $this->type_prestation,
            'statut' => $this->statut,
            'montant_caution' => $this->montant_caution,
            'montant_total' => $this->montant_total,
            'client' => new ClientResource($this->whenLoaded('client')),
            'materiels' => MaterielResource::collection($this->whenLoaded('materiels')),
            'prestations_decoration' => PrestationResource::collection($this->whenLoaded('prestationsDecoration')),
            'elements_decor' => ElementDecorResource::collection($this->whenLoaded('elementsDecor')),
            'devis' => DevisResource::collection($this->whenLoaded('devis')),
        ];
    }
}
```

On choisit **un casing unique et documenté** (recommandé : `snake_case`, comme la
base, pour rester cohérent avec ce qui est déjà fait partout). Le front cesse de
deviner.

---

### 3.4 Disponibilité & stock : le vrai risque métier

État actuel, vérifié dans le code :

- `DisponibiliteController@verifier` renvoie `disponible = !Commande::where(...)->exists()`
  **au niveau global** de la commande, sans regarder *quels* articles ni *quelles*
  quantités.
- `quantite_stock` n'est lu que dans `CatalogueController` (affichage) et jamais
  décrémenté nulle part (`grep` : aucune écriture).

Conséquences réelles :
- On peut réserver 50 tables sur 20 en stock.
- Deux clients sur des articles différents sont vus comme en conflit (faux positif),
  ou l'inverse (faux négatif) selon les dates.
- L'admin valide à l'aveugle.

Deux niveaux de correction :

**(a) Disponibilité par article** — la question n'est pas « une commande existe sur
la plage » mais « cet article a-t-il assez d'unités libres sur la plage ? ».

```php
final readonly class VerifierDisponibiliteMateriel
{
    public function executer(Materiel $materiel, int $quantiteDemandee, CarbonInterface $debut, CarbonInterface $fin): bool
    {
        $dejaReserve = DB::table('commande_materiel')
            ->join('commandes', 'commandes.id', '=', 'commande_materiel.commande_id')
            ->where('commande_materiel.materiel_id', $materiel->id)
            ->whereIn('commandes.statut', ['en_attente', 'valide'])
            ->whereNull('commandes.deleted_at')
            ->where('commandes.date_debut_location', '<=', $fin)
            ->where('commandes.date_fin_location', '>=', $debut)
            ->sum('commande_materiel.quantite');

        return ($materiel->quantite_stock - $dejaReserve) >= $quantiteDemandee;
    }
}
```

**(b) Concurrence** — deux demandes simultanées peuvent passer le check puis écrire.
Il faut verrouiller la ligne article dans la transaction de création :

```php
$materiels = Materiel::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
// vérifier qu'il reste du stock PENDANT la transaction, sinon throw StockInsuffisantException
```

Un index `(materiel_id, commande_id)` et un index composite sur
`commandes (statut, date_debut_location, date_fin_location)` sont indispensables pour
que la somme ne fasse pas un scan complet (voir §4.1).

**Alternative structurante** (si le métier grandit) : une table `reservations`
(`materiel_id`, `commande_id`, `quantite`, `date_debut`, `date_fin`, `statut`) en
source de vérité du stock dans le temps, et `quantite_stock` comme stock physique
total. Ça permet le calendrier par article, les retours partiels, la maintenance
matériel. C'est plus de travail, mais c'est **la** modélisation honnête d'un service
de location.

---

### 3.5 Générer les PDF et envoyer les mails hors de la requête HTTP

`CommandeController@store` génère le PDF **et** envoie le mail en synchrone. Le client
attend que dompdf tourne (souvent 300 ms à 2 s) et, si le SMTP tousse, il voit une
erreur alors que sa commande est **déjà en base** → support téléphonique garanti.

Proposition : un événement `CommandeCreee` + des listeners en queue.

```php
// Dans l'Action, après le commit :
event(new CommandeCreee($commande));

// Listener
final class GenererPdfRecapitulatif implements ShouldQueue
{
    public function handle(CommandeCreee $event): void { /* generation pdf */ }
}

final class EnvoyerConfirmationCommande implements ShouldQueue
{
    public function handle(CommandeCreee $event): void
    {
        Mail::to($event->commande->client->email)->queue(new CommandeConfirmationMail($event->commande));
    }
}
```

Et surtout : **ne pas envoyer de mail sans email**. `client.email` est nullable
(la migration l'a voulu ainsi). Aujourd'hui le code appelle le mail même si l'email
est `null`. Il faut un garde :

```php
if ($commande->client->email !== null) {
    Mail::to($commande->client->email)->queue(new CommandeConfirmationMail($commande));
}
```

Côté exploitation : passer `QUEUE_CONNECTION=database` (la migration `jobs` existe déjà)
et lancer un worker (`php artisan queue:work`). Le `MAIL_MAILER=array` en test est déjà
bon.

---

### 3.6 Authentification admin : corriger le modèle de menace

Ce qui existe : `Admin` + `HasApiTokens`, un endpoint `/admin/login` qui renvoie un
`plainTextToken`, le front le range dans `localStorage` et l'envoie en `Bearer`.
Constat documentaire (doc Sanctum officielle) :

> *« You should not use API tokens to authenticate your own first-party SPA. Instead,
> use Sanctum's built-in SPA authentication features. »*

Un jeton en `localStorage` est lisible par tout script (XSS), il ne peut pas être
`httpOnly`, et `expiration = null` le rend éternel. Pour une SPA first-party servie
sur le même domaine, la bonne configuration est **la session par cookie + CSRF** :

1. publier `config/cors.php` avec `'supports_credentials' => true` ;
2. `bootstrap/app.php` → `$middleware->statefulApi()` ;
3. renseigner `SANCTUM_STATEFUL_DOMAINS` (`localhost:5173`) ;
4. côté axios : `withCredentials: true` et `withXSRFToken: true`, appeler
   `GET /sanctum/csrf-cookie` avant le login ;
5. login via le guard `web` (session), plus de jeton renvoyé au JS.

Cela supprime la classe de vulnérabilité « vol de jeton XSS » et garde `auth:sanctum`
identique sur les routes.

**Compléments indispensables, quel que soit le mode :**

- **Rate limiting** sur `POST /admin/login` (`Route::middleware('throttle:5,1')`), sinon
  le code admin à 6 chiffres tombe par force brute en quelques heures.
- **Expiration des jetons** si on garde les tokens (mobile) : `'expiration' => 60 * 24`,
  et purge planifiée.
- **`Hash::check` en temps constant** — Laravel le fait ; s'assurer de ne pas court-
  circuiter la vérification quand `identifiant` est inconnu (sinon énumération de
  comptes par timing). Comparer toujours les trois `Hash::check`, ou renvoyer un
  message générique.
- Séparer clairement : `mot_de_passe` = ce que l'admin *connaît*, `code_admin` = second
  facteur partagé. À terme, **TOTP** (`pragmarx/google2fa`) est plus propre que le code
  fixe partagé par toute l'équipe.

---

### 3.7 Observabilité minimale dès maintenant

- **`/up`** existe déjà (`withRouting` health) : bien.
- Brancher **Sentry** (`sentry/sentry-laravel`) : les erreurs PDF/mail en prod sinon
  invisibles.
- **Logs structurés** sur les transitions d'état : qui (admin id), quoi (commande),
  quand. `Log::info('commande.statut.change', [...])`.
- Un petit **endpoint de santé métier** `GET /api/health` qui teste DB + storage + queue
  (utile pour le monitoring et pour vérifier que le disque privé est monté).

---

## 4. Base de données

### 4.1 Index manquants et intégrité

Index présents : plutôt bons (`statut`, `nom`, `telephone`, `email`). Manquent :

- `commandes (statut, date_debut_location, date_fin_location)` → requête de
  disponibilité (§3.4).
- `commande_materiel (materiel_id)` et un index unique `(commande_id, materiel_id)`
  pour empêcher d'attacher deux fois le même article.
- Contrainte FK explicite `onDelete('cascade')` sur les pivots (sinon une suppression
  de commande orpheline les lignes) — à vérifier migration par migration.
- `devis (commande_id)` indexé.

### 4.2 Référence lisible + idempotence

Un client se plaint, l'admin cherche... par `id` auto-incrémenté. Ajouter une
**référence métier** : `GDD-2026-000123`.

```php
$table->string('reference')->unique()->after('id');
```

Et pour éviter les **doubles soumissions** (clic double, réseau capricieux), une clé
d'idempotence :

- soit un champ `idempotency_key` unique envoyé par le front (UUID généré au montage
  du formulaire) ;
- soit un token caché côté serveur. Sans ça, chaque double clic crée une commande.

### 4.3 Cycle de vie financier

Aujourd'hui : `montant_caution` figé sur la commande, point. Le métier décrit parle de
*caution*, de *devis*, plus tard peut-être d'*acompte* et de *facture*. Proposer dès
maintenant :

- `montant_total` (figé) sur `commandes` ;
- une table `paiements` (`commande_id`, `type` [caution|acompte|solde], `montant`,
  `statut`, `date`, `moyen`) ou au minimum deux colonnes `caution_payee_at` /
  `caution_remboursee_at` ;
- `devis` : ajouter `version`/`numero` et `statut` (brouillon/envoyé/accepté) pour
  éviter d'écraser le précédent.

C'est peu de travail maintenant, très coûteux après coup.

### 4.4 Données personnelles : disque privé + URL signée

**Le disque `public` pour les pièces d'identité est une fuite.** Correctif :

- `config/filesystems.php` : un disque `prive` (`local`, racine `storage/app/prive`) ;
- stocker via `->store('pieces_identite', 'prive')` ;
- servir via une route admin protégée :
  `GET /api/admin/commandes/{commande}/piece-identite` → `Storage::disk('prive')->download(...)`
  ou `temporaryUrl()` si S3 ;
- ajouter une **purge/retention** (RGPD-like) : les pièces d'identité n'ont pas à
  rester ad vitam après l'événément.

Côté upload, valider aussi la taille et les types réels :
`'piece_identite_photo' => ['required','file','mimes:jpg,jpeg,png,pdf','max:5120']`.
(`mimes` vérifie le contenu, pas seulement l'extension.)

---

## 5. Front-end

### 5.1 Un seul dossier de composants, organisé par fonctionnalité

Il existe **deux** dossiers : `frontend/src/composants/` (Header, Footer, Panier…)
et `frontend/src/components/` (ArticleCard, sections…). C'est la source de confusion
garantie. Cible :

```
src/
├── features/
│   ├── catalogue/      (ArticleCard, SectionLocation, SectionDecoration)
│   ├── commande/       (PanierRecap, FormulaireCoordonnees, usePanier)
│   └── admin/          (ListeDemandes, DevisViewer, CalendrierDisponibilite…)
├── components/         (Design system : Button, Input, Badge, Modal)
├── services/           (api, *Service)
├── context/            (AuthContext)
└── pages/
```

Règle : un composant partagé sans logique métier → `components/` ; un composant lié à
un domaine → `features/<domaine>/`. La casse/grammaire est **français** dans tout le
reste du projet, donc autant rester en français (`features/devis`, etc.) — le
bilinguisme `composants`/`components` est le vrai problème, pas la langue.

### 5.2 Remplacer les `useEffect + fetch` à la main par TanStack Query

Chaque écran réimplémente chargement / erreur / refetch. C'est 40 lignes de
plomberie par écran et des bugs de course (réponse tardive qui écrase un état récent).
`@tanstack/react-query` résout ça en standard : cache, dédoublonnage, retry,
`isPending`, invalidation après mutation.

```jsx
const { data: commandes, isPending } = useQuery({
  queryKey: ['admin', 'commandes', filtre],
  queryFn: () => getCommandes(filtre ? { statut: filtre } : {}),
});

const changerStatut = useMutation({
  mutationFn: ({ id, statut }) => updateStatut(id, statut),
  onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'commandes'] }),
});
```

Et pour la **modification de statut côté admin**, un **optimistic update** (React
Query le supporte nativement, ou `useOptimistic` de React 19 — §5.4) : le badge change
instantanément, l'UI redevient cohérente si l'API refuse.

### 5.3 Un vrai état d'authentification

Aujourd'hui : `localStorage` + `isAuthenticated()` lue à chaque appel, et la redirection
est faite à la main dans `AdminDashboard`/`AdminLogin`. Proposer :

- un `AuthContext` (`admin`, `login`, `logout`, `isAuthenticated`) ;
- un composant de garde `<RequireAdmin>` qui redirige vers `/admin/login` en
  conservant l'URL visée (`state.from`) ;
- si on bascule sur le cookie Sanctum (§3.6), vérifier la session via `GET /admin/me`
  au démarrage (le `localStorage` disparaît complètement).

### 5.4 Tirer parti de React 19 (sans framework meta)

React 19 fournit des primitives qui **suppriment** exactement le boilerplate qu'on a
aujourd'hui :

- `useActionState(action, initialState)` → remplace `const [envoi, setEnvoi]`,
  `const [erreur, setErreur]`, le `try/catch` et le `finally`. Il expose `isPending`
  et l'état de retour (dont les erreurs de validation Laravel `errors`).
- `useOptimistic` → pour les actions admin (valider/refuser une commande) et le panier
  (ajout instantané).
- `use(promise)` → lecture de données en render avec un `<Suspense>` parent (plus
  pertinent couplé à TanStack Query, qui gère déjà le cache).

Exemple de refonte du formulaire de commande :

```jsx
const [etat, soumettre, enCours] = useActionState(async (_prec, formData) => {
  try {
    const reponse = await createCommande(construireFormData(formData));
    return { succes: reponse, erreurs: {} };
  } catch (e) {
    return { erreurs: e.response?.data?.errors ?? {}, message: e.response?.data?.message };
  }
}, { succes: null, erreurs: {} });

<form action={soumettre}>
  ...
  <button disabled={enCours}>{enCours ? 'Envoi…' : 'Valider ma demande'}</button>
</form>
```

On supprime ~4 `useState` et deux `try/catch` par écran.

### 5.5 Panier : `useReducer` + persistance + type de demande explicite

Deux points sur `PanierContext` :

1. Trois `useState` + une dizaine de setters : un **reducer** rend le cycle de vie
   testable et évite les mises à jour partielles incohérentes.
2. Le `type_prestation` est **déduit** (`materiels.length && decoration... ? les_deux : ...`).
   C'est malin mais fragile : un utilisateur qui veut *« décoration seule »* en ajoutant
   une prestation puis en changeant d'avis garde des états résiduels. Rendre le choix
   **explicite** (cases « Location » / « Décoration ») et dériver les sections
   visibles du choix, pas du panier. On affiche ensuite les sections correspondantes.
3. Persister le panier dans `sessionStorage` (survit à un rafraîchissement) et le vider
   après succès.

```jsx
function panierReducer(state, action) {
  switch (action.type) {
    case 'AJOUTER_MATERIEL': { /* fusion par id */ }
    case 'RETIRER_MATERIEL': { /* ... */ }
    case 'VIDER': return ETAT_VIDE;
    default: return state;
  }
}
```

### 5.6 Validation de formulaires : react-hook-form + zod, miroir du Laravel

La validation actuelle est **manuellement dupliquée** entre `StoreCommandeRequest`
(PHP) et `FormulaireCoordonnees.jsx` (JS) — deux sources qui vont diverger. Avec
`react-hook-form` + `zod`, on décrit le schéma une fois côté front, et l'API reste
seule autorité. Mieux (si vous passez en TypeScript) : générer les types depuis
l'OpenAPI/Scribe de Laravel pour que le contrat soit unique.

### 5.7 TypeScript

Le projet n'a ni TS ni PropTypes. Sur une équipe de quatre, TS est le meilleur
investissement qualité/effort : il attrape `commande.client?.nom` sur une relation
non chargée, les fautes de frappe sur `statut`, etc. Migration progressive :
`allowJs: true`, on convertit d'abord `services/` et les DTO de réponse, puis les
composants.

### 5.8 Tailwind v4 : design tokens au lieu de classes dispersées

Tailwind v4 est **CSS-first** (`@theme`). Définir les couleurs de marque une fois :

```css
@import "tailwindcss";

@theme {
  --color-marque-50: oklch(0.97 0.02 155);
  --color-marque-700: oklch(0.45 0.09 155); /* l'actuel emerald-700 */
  --color-accent: oklch(0.8 0.16 85);       /* l'actuel yellow-500 */
}
```

puis `bg-marque-700` / `bg-accent` partout. Aujourd'hui `emerald-*` et `yellow-*` sont
utilisés en dur ; au premier rebranding il faut tout retoucher.

### 5.9 Accessibilité & i18n

- Les modales (`ListeDemandes`, ex-`ArticleModal`) n'ont pas de `role="dialog"`,
  `aria-modal`, ni piège de focus, ni fermeture `Échap`. Correctif : un composant
  `<Modal>` unique dans le design system.
- Les `<img>` sans `width/height` provoquent du CLS (le `ArticleCard` a un conteneur
  fixe, bon réflexe — généraliser).
- Formatage `toLocaleString("fr-FR")` est répété : centraliser dans `formatFcfa()`.
- Prévoir `i18n` (fr par défaut, en) : les libellés sont en dur dans le JSX.

---

## 6. Tests & qualité

### 6.1 Back-end

- **Bonne nouvelle** : Pest est là et les tests couvrent de vrais scénarios.
- **Style** : les tests actuels sont « PHPUnit-style » dans une app Pest
  (`public function test_...`). Adopter la syntaxe Pest (`it('...', function () {})`)
  rendrait la suite plus lisible et cohérente avec l'outil.
- **Manquent** : test de **stock** (réserver plus que le stock doit échouer), test de
  **concurrence** (deux créations), test de **rate limit login**, test **soft-delete/
  corbeille**, test **PII non accessible publiquement** (asserter que `/storage/...`
  ne sert pas la pièce), test du **second facteur** incorrect.
- **Architecture** : `tests/Unit` pour `Money`, `CalculateurCaution`, enums.

### 6.2 Front-end

Zéro test front aujourd'hui. Minimum viable :

- **Vitest + Testing Library** : `PanierRecap` (totaux), `panierReducer`, la
  construction du `FormData` (le bug le plus probable survient là), `formatFcfa`.
- **Playwright** (E2E) : parcours client complet (sélection → coordonnées → succès)
  et parcours admin (login → valider → générer devis → télécharger). C'est ce qui
  garantit que les deux moitiés du contrat se parlent.

### 6.3 Analyse statique & CI

- **Larastan / PHPStan** niveau 5+ : attrape les relations non chargées, les `null`.
- **oxlint** déjà en place côté front : ajouter `--deny-warnings` en CI.
- **CI GitHub Actions** : `composer test` + `pint --test` + `npm run lint` + `npm run build`
  + `vitest`. Rien de tout ça n'est bloquant aujourd'hui.
- **OpenAPI/Scribe** : générer la doc d'API → sert de contrat front/back et de base à
  la génération de types TS.

---

## 7. Sécurité — checklist

| Sujet | État | Action |
|---|---|---|
| Pièces d'identité sur disque public | **Vulnérable** | disque privé + route signée (§4.4) |
| Jeton admin en localStorage | **À risque** | cookie Sanctum SPA (§3.6) |
| Jeton éternel (`expiration=null`) | **À risque** | expiration + purge |
| Force brute login | **À risque** | `throttle:5,1` |
| Validation upload (mime/taille) | Partielle | `mimes` + `max` |
| CORS | Non publié | `config/cors.php` + credentials |
| Mass assignment | OK (`$fillable`) | — |
| Secrets | OK (env) | — |
| Autorisation fine (Policies) | Absente | Policy `Commande` si multi-rôles |
| Logs d'audit | Absents | log transitions (§3.7) |

---

## 8. Détails de code à corriger tout de suite

- **`config/database.php` lignes 78 & 101** : `PDO::MYSQL_ATTR_SSL_CA` est déprécié en
  PHP 8.5 → `Pdo\Mysql::ATTR_SSL_CA` (déprécation visible à chaque `php artisan test`).
  À conditionner par version si vous ciblez aussi PHP 8.3 (`defined('Pdo\Mysql::ATTR_SSL_CA')`).
- **`CommandeConfirmationMail` envoyé même si l'email est `null`** : guard requis (§3.5).
- **Devis** : deux dossiers `devis/` créés à la volée + `genererRecapitulatif` écrit
  aussi dans `devis/` (nommage : c'est un *récapitulatif*, pas un *devis*). Séparer
  `recapitulatif/` et `devis/`.
- **`ArticleModal`/`Panier` legacy** dans `composants/` : désormais morts après la
  refonte des pages, à supprimer pour ne pas laisser deux générations cohabiter.

---

## 9. Feuille de route priorisée

### P0 — avant toute prod (bloquant métier/sécurité)
1. Disponibilité **par article** + vérification de stock sous `lockForUpdate` (§3.4). *~2-3 j*
2. Pièces d'identité sur **disque privé** + route protégée (§4.4). *~0,5 j*
3. Rate limiting login + expiration des jetons (§3.6). *~0,5 j*
4. Garde « email null » avant envoi mail (§3.5). *~0,2 j*
5. `montant_total` figé sur la commande + référence lisible (§4.2, §4.3). *~0,5 j*

### P1 — solidité
6. Actions + DTO + Enums + `Money` (§3.1-3.3). *~2-3 j*
7. API Resources + contrat de casing unique (§3.3). *~1 j*
8. Événement `CommandeCreee` + listeners en queue, worker actif (§3.5). *~1 j*
9. Migration cookie Sanctum SPA + AuthContext/RequireAdmin (§3.6, §5.3). *~1,5 j*
10. TanStack Query côté front (§5.2). *~1,5 j*
11. Index DB + FK cascades + idempotence (§4.1, §4.2). *~0,5 j*

### P2 — qualité & DX
12. TypeScript progressif + zod/react-hook-form (§5.6, §5.7). *~3-4 j*
13. Fusion des dossiers de composants, design system `<Button>/<Modal>/<Input>` (§5.1, §5.9). *~2 j*
14. Tailwind `@theme` tokens (§5.8). *~0,5 j*
15. Vitest + Playwright, Larastan, CI (§6). *~2-3 j*
16. Observabilité (Sentry, logs d'audit, health métier) (§3.7). *~1 j*

### P3 — produit
17. TOTP au lieu du code admin partagé ; gestion multi-admins/rôles. *~2-3 j*
18. Cycle financier complet (paiements) + versions de devis (§4.3). *~3-5 j*
19. Notification SMS/WhatsApp (marché local) en plus de l'email. *~2 j*
20. Table `reservations` comme source de vérité du stock dans le temps (§3.4). *~4-6 j*

---

## 10. Arborescence cible (proposition)

```
backend/app/
├── Actions/
│   ├── Commande/{CreerCommandeAction, ValiderCommandeAction}.php
│   └── Devis/{GenererDevisAction}.php
├── DataTransferObjects/            # readonly
├── Enums/{TypePrestation, StatutCommande, TypePaiement}.php
├── Http/
│   ├── Controllers/Api/
│   ├── Requests/
│   └── Resources/                  # API Resources
├── Models/
├── Services/
│   ├── Caution/{CalculateurCaution}.php
│   ├── Stock/{VerifierDisponibiliteMateriel}.php
│   ├── Pdf/{GenerateurPdfCommande}.php
│   └── ...
├── Events/  Listeners/
└── Policies/

frontend/src/
├── features/
│   ├── catalogue/  commande/  admin/
├── components/                     # design system
├── services/  contexts/  hooks/  utils/
└── pages/
```

---

## 11. Conclusion

Le projet a déjà les fondations et une vraie culture de test. Le chemin vers un
produit fiable n'est **pas** d'ajouter des écrans : c'est de traiter le **stock par
article**, de **protéger les données personnelles**, de **sortir le métier des
contrôleurs** (Actions + Enums + Value Objects) et de **stabiliser le contrat d'API**
(Resources). Côté front, TanStack Query + React 19 Actions + TypeScript feront gagner
plus en fiabilité qu'en lignes de code.

Faites d'abord P0 : ces 5 points représentent ~4 jours et éliminent l'essentiel du
risque opérationnel. Le reste est de la consolidation à étaler sans bloquer la mise
en ligne.

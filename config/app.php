<?php
// Réglages du site + petites fonctions utiles partout

// Valeurs par défaut pour Laragon. En ligne, config/production.php les définit avant
// (voir config/production.exemple.php).
defined('EN_PRODUCTION')    || define('EN_PRODUCTION', false);
defined('EMAIL_COMMANDES')  || define('EMAIL_COMMANDES', 'cousin@exemple.com'); // en local, arrive dans Mailpit
defined('EMAIL_EXPEDITEUR') || define('EMAIL_EXPEDITEUR', 'porosi@parfumeria.ks.test');

// En ligne : les erreurs PHP ne s'affichent plus aux clients,
// elles sont écrites dans logs/erreurs.log à la place
if (EN_PRODUCTION) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/erreurs.log');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Le visiteur est-il en https ? (certains hébergeurs passent par un proxy qui
// l'indique dans X-Forwarded-Proto au lieu de $_SERVER['HTTPS'])
function connexion_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

// TODO : nom provisoire, à remplacer par le vrai nom choisi par le cousin
define('NOM_SITE', 'Parfumeria');
define('DEVISE', '€');

// Bandeau tout en haut de chaque page
const MESSAGE_BANDEAU = 'Dërgesa në Kosovë, Shqipëri dhe Maqedoninë e Veriut · Pagesa në dorëzim';

// Contact (page "Kontakti" + pied de page). Laisser vide = pas affiché.
// TODO : demander au cousin son email et ses comptes.
const CONTACT_TELEPHONE = '+383 49 226 631';   // format international (aussi utilisé pour WhatsApp et Viber)
const CONTACT_EMAIL     = '';   // ex : 'info@parfumeria-ks.com'
const LIEN_INSTAGRAM    = '';   // ex : 'https://www.instagram.com/...'
const LIEN_FACEBOOK     = '';
const LIEN_TIKTOK       = '';

// Pays de livraison et frais de livraison (en euros).
// TODO : mettre les vrais frais décidés avec le cousin (0 = gratuit).
const PAYS_LIVRAISON = [
    'Kosovë'             => 0.00,
    'Shqipëri'           => 0.00,
    'Maqedonia e Veriut' => 0.00,
];

// Livraison gratuite à partir de ce montant (articles après code promo). 0 = jamais gratuite.
const LIVRAISON_GRATUITE_DES = 25.00;

const STATUTS_COMMANDE = [
    'en_attente' => 'Në pritje',
    'confirmee'  => 'E konfirmuar',
    'expediee'   => 'E dërguar',
    'livree'     => 'E dorëzuar',
    'annulee'    => 'E anuluar',
];

// Tris proposés dans le catalogue (la clé va dans l'URL : ?tri=prix_asc)
const TRIS = [
    'recommande' => 'Të rekomanduarat',
    'nouveau'    => 'Më të rejat',
    'prix_asc'   => 'Çmimi: nga më i ulëti',
    'prix_desc'  => 'Çmimi: nga më i larti',
];

// Protège l'affichage contre les failles XSS
function e(?string $texte): string
{
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}

// Construit une URL du site : url('produit', ['id' => 3]) -> index.php?page=produit&id=3
function url(string $page = 'home', array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

// URL complète avec le domaine (pour les liens dans les emails)
function url_absolue(string $page = 'home', array $params = []): string
{
    $dossier = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return (connexion_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dossier . '/' . url($page, $params);
}

function redirect(string $page = 'home', array $params = []): void
{
    header('Location: ' . url($page, $params));
    exit;
}

function prix(float|string $montant): string
{
    return number_format((float) $montant, 2, ',', ' ') . ' ' . DEVISE;
}

// 100 -> "100 ml"
function ml(int|string $contenance): string
{
    return (int) $contenance . ' ml';
}

// Pourcentage de réduction : reduction(39.90, 59.90) -> 33. Renvoie 0 s'il n'y a pas de promo.
function reduction(float|string|null $prix, float|string|null $prixAncien): int
{
    if (!$prixAncien || (float) $prixAncien <= (float) $prix) {
        return 0;
    }
    return (int) round((1 - (float) $prix / (float) $prixAncien) * 100);
}

// "Maison Ambré" -> "maison-ambre" (pour les adresses des pages de marques)
function slug(string $texte): string
{
    $texte = strtr(mb_strtolower($texte), ['ë' => 'e', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'ô' => 'o', 'ü' => 'u', 'ö' => 'o']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $texte), '-');
}

// Slug qui n'existe pas encore dans la table : si "dior" est pris, on essaie "dior-2", "dior-3"...
// $saufId : la ligne en cours de renommage (elle peut garder son propre slug)
function slug_libre(string $table, string $texte, int $saufId = 0): string
{
    // Le nom de table ne vient jamais du client, mais on le vérifie quand même (il va dans le SQL)
    if (!in_array($table, ['marques', 'notes'], true)) {
        throw new InvalidArgumentException('Table non prévue : ' . $table);
    }
    $base = slug($texte) ?: 'pa-emer';
    $slug = $base;
    $verif = Database::getConnexion()->prepare("SELECT COUNT(*) FROM $table WHERE slug = ? AND id <> ?");
    for ($i = 2; $verif->execute([$slug, $saufId]) && $verif->fetchColumn() > 0; $i++) {
        $slug = $base . '-' . $i;
    }
    return $slug;
}

// Message affiché une seule fois après une redirection
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Jeton CSRF : protège les formulaires POST
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_champ(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verifier(): void
{
    // Jeton vide en session = aucun formulaire affiché : on refuse aussi
    $jeton = $_SESSION['csrf'] ?? '';
    $envoye = $_POST['csrf'] ?? '';
    if ($jeton === '' || !is_string($envoye) || !hash_equals($jeton, $envoye)) {
        http_response_code(403);
        die('Formulari ka skaduar. Kthehuni mbrapa, rifreskoni faqen dhe provoni përsëri.');
    }
}

function est_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Lit un champ texte d'un formulaire POST, sans espaces autour.
// Renvoie '' si le champ manque ou si quelqu'un envoie un tableau (champ[]=...) :
// sans cette vérification, trim() provoquerait une TypeError (page d'erreur 500).
// Pareil si le texte n'est pas de l'UTF-8 valide (envoyé par un robot) : MySQL le refuserait
// avec une erreur 500 au moment d'enregistrer.
function champ_post(string $nom): string
{
    $valeur = $_POST[$nom] ?? '';
    return is_string($valeur) && mb_check_encoding($valeur, 'UTF-8') ? trim($valeur) : '';
}

// Nombre entier envoyé par un formulaire POST. Champ absent ou vide -> $defaut.
// À utiliser à la place de (int) $_POST[...] : (int) d'un tableau (champ[]=...) donne 1,
// ce qui viserait sans le vouloir le produit ou la contenance n°1.
function entier_post(string $nom, int $defaut = 0): int
{
    $valeur = champ_post($nom);
    return $valeur === '' ? $defaut : (int) $valeur;
}

// Pareil pour un paramètre de l'URL (?id=3). Absent ou tableau -> 0.
function entier_get(string $nom): int
{
    $valeur = $_GET[$nom] ?? '';
    return is_string($valeur) ? (int) $valeur : 0;
}

// Numéro de téléphone : chiffres et espaces, avec ou sans + au début
function telephone_valide(string $telephone): bool
{
    return (bool) preg_match('/^\+?[0-9 ]{8,20}$/', $telephone);
}

// ---------- Liens WhatsApp / Viber vers le numéro du cousin ----------
// wa.me, viber et tel: veulent le numéro avec uniquement des chiffres : '+383 49 226 631' -> '38349226631'
function telephone_chiffres(): string
{
    return preg_replace('/\D/', '', CONTACT_TELEPHONE);
}

// Ouvre une discussion WhatsApp, avec un message déjà écrit si $texte est donné
function lien_whatsapp(string $texte = ''): string
{
    return 'https://wa.me/' . telephone_chiffres() . ($texte !== '' ? '?text=' . rawurlencode($texte) : '');
}

// Viber ne permet pas de pré-remplir le message vers un numéro précis : on ouvre juste la discussion
function lien_viber(): string
{
    return 'viber://chat?number=%2B' . telephone_chiffres();
}

// ---------- Livraison ----------

// Frais de livraison pour ce pays, en tenant compte de la livraison gratuite.
// $montant = prix des articles après la remise du code promo.
function frais_livraison(string $pays, float $montant): float
{
    if (LIVRAISON_GRATUITE_DES > 0 && $montant >= LIVRAISON_GRATUITE_DES) {
        return 0.0;
    }
    return (float) (PAYS_LIVRAISON[$pays] ?? 0);
}

// La livraison est-elle payante quelque part ? (sinon, inutile de parler de "livraison gratuite dès 25 €")
function livraison_payante(): bool
{
    return LIVRAISON_GRATUITE_DES > 0 && max(PAYS_LIVRAISON) > 0;
}

// ---------- Liste d'envies (en session) ----------
function est_favori(int $produitId): bool
{
    return isset($_SESSION['favoris'][$produitId]);
}

// Bouton cœur pour ajouter/retirer un produit de la liste d'envies.
// $classe : 'coeur-carte' (petit rond sur la photo) ou 'coeur-fiche' (bouton sur la fiche produit)
function bouton_favori(int $produitId, string $classe): string
{
    $actif = est_favori($produitId);
    $texte = $actif ? 'Hiq nga lista e dëshirave' : 'Shto në listën e dëshirave';

    return '<form method="post" action="' . url('favori_basculer') . '" class="form-coeur ' . $classe . '">'
        . csrf_champ()
        . '<input type="hidden" name="produit_id" value="' . $produitId . '">'
        // Pour revenir sur la même page après le clic
        . '<input type="hidden" name="retour" value="' . e($_SERVER['QUERY_STRING'] ?? '') . '">'
        . '<button type="submit" class="coeur' . ($actif ? ' coeur-actif' : '') . '" aria-pressed="' . ($actif ? 'true' : 'false') . '" title="' . $texte . '">'
        . '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>'
        . '<span class="coeur-texte">' . $texte . '</span>'
        . '</button>'
        . '</form>';
}

function utilisateur_connecte(): ?array
{
    return $_SESSION['utilisateur'] ?? null;
}

function est_admin(): bool
{
    return !empty($_SESSION['utilisateur']['est_admin']);
}

// À appeler au début de chaque page admin : bloque les visiteurs et les clients
function admin_requis(): void
{
    if (!utilisateur_connecte()) {
        flash('erreur', 'Kyçuni për të hyrë në panelin e administrimit.');
        redirect('connexion');
    }
    if (!est_admin()) {
        http_response_code(403);
        render('erreur404');
        exit;
    }
}

// Affiche une vue entourée du header et du footer
function render(string $vue, array $donnees = []): void
{
    extract($donnees);
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    $categoriesMenu = Categorie::toutes();
    $nbArticlesPanier = array_sum($_SESSION['panier'] ?? []);

    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/' . $vue . '.php';
    require __DIR__ . '/../views/layout/footer.php';
}

<?php
// Page actuelle et filtres, pour souligner le bon lien du menu
$pageActuelle = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';
$categorieUrl = is_string($_GET['categorie'] ?? null) ? $_GET['categorie'] : '';
$rechercheEntete = is_string($_GET['q'] ?? null) ? $_GET['q'] : '';
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= !empty($titre) ? e($titre) . ' — ' : '' ?><?= NOM_SITE ?></title>
    <?php
    // ?v=date de modification : quand le fichier change, l'adresse change,
    // donc le navigateur télécharge la nouvelle version au lieu de garder l'ancienne en mémoire
    $version = fn(string $fichier) => $fichier . '?v=' . filemtime(__DIR__ . '/../../' . $fichier);
    ?>
    <link rel="icon" type="image/svg+xml" href="<?= $version('public/img/icone.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $version('public/css/style.css') ?>">
</head>
<body>

<p class="annonce"><?= e(MESSAGE_BANDEAU) ?><?= livraison_payante() ? ' · Dërgesë falas mbi ' . prix(LIVRAISON_GRATUITE_DES) : '' ?></p>

<header class="entete">
    <div class="conteneur entete-ligne">
        <a class="logo" href="<?= url('home') ?>" aria-label="<?= NOM_SITE ?>, ballina">
            <svg width="30" height="30" viewBox="0 0 200 200" aria-hidden="true">
                <g fill="currentColor"><rect x="82" y="26" width="36" height="26" rx="5"/><rect x="91" y="52" width="18" height="12"/><rect x="46" y="64" width="108" height="112" rx="26"/></g>
            </svg>
            <span class="logo-texte"><?= NOM_SITE ?></span>
        </a>

        <!-- Recherche : envoie vers le catalogue (index.php?page=produits&q=...) -->
        <form class="recherche" action="index.php" method="get" role="search">
            <input type="hidden" name="page" value="produits">
            <label class="invisible" for="q">Kërko parfum</label>
            <input type="search" id="q" name="q" value="<?= e($rechercheEntete) ?>" placeholder="Kërko parfum ose markë…" maxlength="100">
            <button type="submit" aria-label="Kërko">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </form>

        <nav class="menu-compte" aria-label="Llogaria dhe shporta">
            <?php if (utilisateur_connecte()): ?>
                <?php if (est_admin()): ?>
                    <a class="lien-admin" href="<?= url('admin') ?>">Admin</a>
                <?php endif; ?>
                <a href="<?= url('mes_commandes') ?>">Porositë e mia</a>
                <form method="post" action="<?= url('deconnexion') ?>">
                    <?= csrf_champ() ?>
                    <button type="submit" class="lien">Çkyçu</button>
                </form>
            <?php else: ?>
                <a href="<?= url('connexion') ?>">Kyçu</a>
            <?php endif; ?>
            <a class="lien-favoris" href="<?= url('favoris') ?>" aria-label="Lista e dëshirave (<?= count($_SESSION['favoris'] ?? []) ?>)">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                <?php if (!empty($_SESSION['favoris'])): ?><span class="pastille"><?= count($_SESSION['favoris']) ?></span><?php endif; ?>
            </a>
            <a class="lien-panier" href="<?= url('panier') ?>">Shporta <span class="pastille"><?= (int) $nbArticlesPanier ?></span></a>
        </nav>
    </div>

    <!-- Menu principal : Ballina, Meshkuj, Femra, Unisex, Në zbritje, Kontakti -->
    <nav class="menu-principal" aria-label="Menyja kryesore">
        <div class="conteneur">
            <a href="<?= url('home') ?>" <?= $pageActuelle === 'home' ? 'aria-current="page"' : '' ?>>Ballina</a>
            <?php foreach ($categoriesMenu as $cat): ?>
                <a href="<?= url('produits', ['categorie' => $cat['slug']]) ?>" <?= $pageActuelle === 'produits' && $categorieUrl === $cat['slug'] ? 'aria-current="page"' : '' ?>><?= e($cat['nom']) ?></a>
            <?php endforeach; ?>
            <a href="<?= url('produits', ['promo' => 1]) ?>" class="lien-promo" <?= $pageActuelle === 'produits' && ($_GET['promo'] ?? '') === '1' ? 'aria-current="page"' : '' ?>>Në zbritje</a>
            <a href="<?= url('contact') ?>" <?= $pageActuelle === 'contact' ? 'aria-current="page"' : '' ?>>Kontakti</a>
        </div>
    </nav>
</header>

<?php
// L'accueil prend toute la largeur (grande bannière) ; les autres pages sont centrées dans .conteneur
$pleineLargeur = ($vue === 'home');
?>
<main class="contenu<?= $pleineLargeur ? ' contenu-accueil' : '' ?>">
<?php if (!$pleineLargeur): ?><div class="conteneur"><?php endif; ?>
    <?php if ($flash): ?>
        <div class="<?= $pleineLargeur ? 'conteneur' : '' ?>">
            <p class="message message-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
        </div>
    <?php endif; ?>

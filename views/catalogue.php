<?php
// Filtres actuellement dans l'URL, pour construire les liens sans perdre les autres filtres
$filtresActuels = array_filter([
    'categorie' => $categorieActive['slug'] ?? null,
    'marque'    => $marqueActive['slug'] ?? null,
    'note'      => $noteActive['slug'] ?? null,
    'q'         => $recherche !== '' ? $recherche : null,
    'promo'     => $promo ? 1 : null,
    'tri'       => $tri !== 'recommande' ? $tri : null,
]);
// lienFiltre('note', 'vanilje') : même page avec ce filtre changé (null = filtre retiré)
$lienFiltre = function (string $nom, ?string $valeur) use ($filtresActuels): string {
    $params = $filtresActuels;
    if ($valeur === null) {
        unset($params[$nom]);
    } else {
        $params[$nom] = $valeur;
    }
    return url('produits', $params);
};
?>
<p class="fil"><a href="<?= url('home') ?>">Ballina</a> / <?= e($titre) ?></p>

<div class="titre-ligne">
    <h1><?= e($titre) ?></h1>
    <p class="note"><?= count($produits) ?> parfum<?= count($produits) > 1 ? 'e' : '' ?></p>
</div>

<div class="catalogue">
    <aside class="filtres-colonne" aria-label="Filtrat">
        <details open>
            <summary>Kategoria</summary>
            <ul>
                <li><a href="<?= $lienFiltre('categorie', null) ?>" <?= !$categorieActive ? 'aria-current="true"' : '' ?>>Të gjitha</a></li>
                <?php foreach ($categoriesMenu as $cat): ?>
                    <li><a href="<?= $lienFiltre('categorie', $cat['slug']) ?>" <?= ($categorieActive['id'] ?? 0) === $cat['id'] ? 'aria-current="true"' : '' ?>><?= e($cat['nom']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details open>
            <summary>Aroma</summary>
            <ul class="puces-notes puces-petites">
                <?php foreach ($notes as $n): ?>
                    <li>
                        <?php $actif = ($noteActive['id'] ?? 0) === $n['id']; ?>
                        <a class="puce" href="<?= $lienFiltre('note', $actif ? null : $n['slug']) ?>" <?= $actif ? 'aria-current="true"' : '' ?>><?= e($n['nom']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>

        <?php if ($marques): ?>
            <details open>
                <summary>Marka</summary>
                <ul>
                    <li><a href="<?= $lienFiltre('marque', null) ?>" <?= !$marqueActive ? 'aria-current="true"' : '' ?>>Të gjitha</a></li>
                    <?php foreach ($marques as $m): ?>
                        <li><a href="<?= $lienFiltre('marque', $m['slug']) ?>" <?= ($marqueActive['id'] ?? 0) === $m['id'] ? 'aria-current="true"' : '' ?>><?= e($m['nom']) ?> <small>(<?= (int) $m['nb_produits'] ?>)</small></a></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>

        <p><a class="lien-promo-filtre" href="<?= $lienFiltre('promo', $promo ? null : '1') ?>" <?= $promo ? 'aria-current="true"' : '' ?>><?= $promo ? '✓ ' : '' ?>Vetëm në zbritje</a></p>

        <?php if ($filtresActuels): ?>
            <p><a href="<?= url('produits') ?>">Hiq të gjitha filtrat</a></p>
        <?php endif; ?>
    </aside>
    <script>
        // Sur téléphone, les filtres ouverts poussent les parfums tout en bas :
        // on les replie (un clic sur "Kategoria", "Aroma"... les rouvre).
        // Sans JavaScript, ils restent simplement ouverts.
        if (window.matchMedia('(max-width: 900px)').matches) {
            document.querySelectorAll('.filtres-colonne details').forEach(function (bloc) {
                bloc.removeAttribute('open');
            });
        }
    </script>

    <div>
        <!-- Tri : petit formulaire GET qui garde les autres filtres en champs cachés -->
        <form class="tri" method="get" action="index.php">
            <input type="hidden" name="page" value="produits">
            <?php foreach ($filtresActuels as $nom => $valeur): ?>
                <?php if ($nom !== 'tri'): ?>
                    <input type="hidden" name="<?= e($nom) ?>" value="<?= e((string) $valeur) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <label for="tri">Rendit sipas</label>
            <select id="tri" name="tri" onchange="this.form.submit()">
                <?php foreach (TRIS as $cle => $libelle): ?>
                    <option value="<?= e($cle) ?>" <?= $tri === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="bouton bouton-petit">OK</button></noscript>
        </form>

        <?php if (empty($produits)): ?>
            <div class="vide">
                <p>Nuk u gjet asnjë parfum me këto filtra.</p>
                <p><a class="bouton" href="<?= url('produits') ?>">Shiko të gjitha parfumet</a></p>
            </div>
        <?php else: ?>
            <div class="grille grille-catalogue">
                <?php foreach ($produits as $p): ?>
                    <?php require __DIR__ . '/partials/carte.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

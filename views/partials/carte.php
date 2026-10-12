<?php
// Carte d'un produit dans une grille. Attend la variable $p (une ligne de Produit::catalogue()).
$reduction = reduction($p['prix_min'], $p['prix_ancien_min']); // promo sur le prix affiché ("nga ...")
$reductionMax = (int) $p['reduction_max'];                    // promo sur une autre contenance
$epuise = (int) $p['stock_total'] === 0;
$lienProduit = url('produit', ['id' => $p['id']]);
?>
<article class="carte">
    <a class="carte-visuel" href="<?= $lienProduit ?>" tabindex="-1" aria-hidden="true">
        <?php if ($p['image']): ?>
            <img src="public/img/produits/<?= e($p['image']) ?>" alt="" loading="lazy">
        <?php else: ?>
            <?php require __DIR__ . '/flacon.php'; ?>
        <?php endif; ?>
        <?php if ($epuise): ?>
            <span class="badge badge-epuise">I shitur</span>
        <?php elseif ($reduction && $reduction >= $reductionMax): ?>
            <span class="badge badge-promo">-<?= $reduction ?>%</span>
        <?php elseif ($reductionMax): ?>
            <span class="badge badge-promo">deri -<?= $reductionMax ?>%</span>
        <?php endif; ?>
    </a>
    <!-- Cœur en haut à droite de la photo (en dehors du lien : un formulaire ne peut pas être dans un <a>) -->
    <?= bouton_favori((int) $p['id'], 'coeur-carte') ?>

    <div class="carte-infos">
        <?php if ($p['marque']): ?>
            <span class="carte-marque"><?= e($p['marque']) ?></span>
        <?php endif; ?>
        <h3 class="carte-nom"><a href="<?= $lienProduit ?>"><?= e($p['nom']) ?></a></h3>
        <p class="carte-prix">
            <?php if ((int) $p['nb_variantes'] > 1): ?><span class="nga">nga</span><?php endif; ?>
            <strong class="<?= $reduction ? 'prix-promo' : '' ?>"><?= prix($p['prix_min']) ?></strong>
            <?php if ($reduction): ?>
                <s><span class="invisible">Çmimi i vjetër:</span><?= prix($p['prix_ancien_min']) ?></s>
            <?php endif; ?>
        </p>
    </div>

    <?php if ($epuise): ?>
        <span class="bouton bouton-carte bouton-desactive" aria-disabled="true">I shitur</span>
    <?php elseif ((int) $p['nb_variantes'] === 1): ?>
        <!-- Une seule contenance : on peut l'ajouter directement au panier depuis la carte -->
        <form method="post" action="<?= url('panier_ajouter') ?>">
            <?= csrf_champ() ?>
            <input type="hidden" name="variante_id" value="<?= (int) $p['premiere_variante'] ?>">
            <input type="hidden" name="quantite" value="1">
            <button type="submit" class="bouton bouton-carte">Shto në shportë</button>
        </form>
    <?php else: ?>
        <a class="bouton bouton-carte bouton-contour" href="<?= $lienProduit ?>">Zgjidh sasinë</a>
    <?php endif; ?>
</article>

<!-- 1. Grande bannière -->
<section class="hero">
    <div class="conteneur hero-ligne">
        <div class="hero-texte">
            <p class="sur-titre">Koleksioni i ri</p>
            <h1>Aroma që flet<br><em>para teje</em></h1>
            <p>Parfume për meshkuj, femra dhe unisex. Zgjidhni aromën tuaj dhe paguani vetëm kur ta merrni.</p>
            <div class="hero-boutons">
                <a class="bouton bouton-or" href="<?= url('produits') ?>">Shiko të gjitha parfumet</a>
                <a class="bouton bouton-clair" href="<?= url('produits', ['promo' => 1]) ?>">Në zbritje</a>
            </div>
        </div>
        <div class="hero-visuel" aria-hidden="true">
            <?php require __DIR__ . '/partials/flacon.php'; ?>
        </div>
    </div>
</section>

<!-- 2. Les trois catégories en grandes tuiles -->
<section class="section">
    <div class="conteneur">
        <div class="tuiles-categories">
            <?php foreach ($categoriesMenu as $cat): ?>
                <a class="tuile-categorie tuile-<?= e($cat['slug']) ?>" href="<?= url('produits', ['categorie' => $cat['slug']]) ?>">
                    <span class="tuile-petit">Parfume për</span>
                    <span class="tuile-nom"><?= e($cat['nom']) ?></span>
                    <span class="lien-fleche">Shiko <span aria-hidden="true">→</span></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3. Produits mis en avant -->
<?php if ($vedettes): ?>
    <?php $section = ['titre' => 'Më të preferuarat', 'lien' => null, 'produits' => $vedettes];
    require __DIR__ . '/partials/section.php'; ?>
<?php endif; ?>

<!-- 4. Nouveautés -->
<?php if ($nouveautes): ?>
    <?php $section = ['titre' => 'Të rejat', 'lien' => url('produits', ['tri' => 'nouveau']), 'produits' => $nouveautes, 'classe' => 'section-fond'];
    require __DIR__ . '/partials/section.php'; ?>
<?php endif; ?>

<!-- 5. Choisir par note olfactive -->
<section class="section">
    <div class="conteneur">
        <div class="section-entete">
            <h2>Zgjidh sipas aromës</h2>
        </div>
        <ul class="puces-notes">
            <?php foreach ($notes as $note): ?>
                <li><a class="puce" href="<?= url('produits', ['note' => $note['slug']]) ?>"><?= e($note['nom']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- 6. Promotions -->
<?php if ($promos): ?>
    <?php $section = ['titre' => 'Në zbritje', 'lien' => url('produits', ['promo' => 1]), 'produits' => $promos, 'classe' => 'section-promo'];
    require __DIR__ . '/partials/section.php'; ?>
<?php endif; ?>

<!-- 7. Une section par catégorie -->
<?php foreach ($sectionsCategories as $sc): ?>
    <?php $section = [
        'titre'    => 'Për ' . mb_strtolower($sc['categorie']['nom']),
        'lien'     => url('produits', ['categorie' => $sc['categorie']['slug']]),
        'produits' => $sc['produits'],
    ];
    require __DIR__ . '/partials/section.php'; ?>
<?php endforeach; ?>

<!-- 8. Marques (affichées seulement si des produits en ont) -->
<?php if ($marques): ?>
    <section class="section section-fond">
        <div class="conteneur">
            <div class="section-entete">
                <h2>Markat</h2>
            </div>
            <ul class="tuiles-marques">
                <?php foreach ($marques as $m): ?>
                    <li>
                        <a href="<?= url('produits', ['marque' => $m['slug']]) ?>">
                            <span class="marque-nom"><?= e($m['nom']) ?></span>
                            <small><?= (int) $m['nb_produits'] ?> parfum<?= (int) $m['nb_produits'] > 1 ? 'e' : '' ?></small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

<!-- 9. Nos engagements -->
<section class="section">
    <div class="conteneur">
        <ul class="avantages">
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                <h3>Zgjedhje e gjerë</h3>
                <p>Aroma për çdo shije: të freskëta, të ëmbla, drusore e shumë të tjera.</p>
            </li>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="7" cy="17.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="17.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                <h3>Dërgesë e shpejtë</h3>
                <p>Në Kosovë, Shqipëri dhe Maqedoninë e Veriut.</p>
            </li>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                <h3>Pagesa në dorëzim</h3>
                <p>Asnjë pagesë online: paguani me para në dorë kur ta merrni porosinë.</p>
            </li>
        </ul>
    </div>
</section>

<?php
// Une section de l'accueil : titre + lien "Shiko më shumë" + grille de cartes.
// Attend : $section = ['titre' => ..., 'lien' => url ou null, 'produits' => [...], 'classe' => '' (facultatif)]
?>
<section class="section <?= e($section['classe'] ?? '') ?>">
    <div class="conteneur">
        <div class="section-entete">
            <h2><?= e($section['titre']) ?></h2>
            <?php if (!empty($section['lien'])): ?>
                <a class="lien-fleche" href="<?= $section['lien'] ?>">Shiko më shumë <span aria-hidden="true">→</span></a>
            <?php endif; ?>
        </div>
        <div class="grille">
            <?php foreach ($section['produits'] as $p): ?>
                <?php require __DIR__ . '/carte.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

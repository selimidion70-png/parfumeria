<h1>Lista e dëshirave</h1>

<?php if (empty($produits)): ?>
    <div class="vide">
        <p>Lista juaj e dëshirave është e zbrazët. Klikoni zemrën ♡ te një parfum për ta ruajtur këtu.</p>
        <p><a class="bouton" href="<?= url('produits') ?>">Shiko të gjitha parfumet</a></p>
    </div>
<?php else: ?>
    <div class="grille grille-catalogue">
        <?php foreach ($produits as $p): ?>
            <?php require __DIR__ . '/partials/carte.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

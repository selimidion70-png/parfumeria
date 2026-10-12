<?php require __DIR__ . '/_menu.php'; ?>

<div class="titre-ligne">
    <h1>Produktet</h1>
    <a class="bouton" href="<?= url('admin_produit') ?>">+ Produkt i ri</a>
</div>

<div class="tableau-defilant">
    <table class="tableau">
        <thead>
            <tr>
                <th scope="col"><span class="invisible">Foto</span></th>
                <th scope="col">Parfumi</th>
                <th scope="col">Për kë</th>
                <th scope="col">Çmimi</th>
                <th scope="col">Stoku</th>
                <th scope="col">Në dyqan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produits as $p): ?>
                <?php $reduc = reduction($p['prix_min'], $p['prix_ancien_min']); ?>
                <tr class="<?= $p['actif'] ? '' : 'ligne-inactive' ?>">
                    <td class="cellule-miniature">
                        <?php if ($p['image']): ?>
                            <img class="miniature" src="public/img/produits/<?= e($p['image']) ?>" alt="">
                        <?php else: ?>
                            <span class="miniature"></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['marque']): ?><small><?= e($p['marque']) ?></small><br><?php endif; ?>
                        <a href="<?= url('admin_produit', ['id' => $p['id']]) ?>"><strong><?= e($p['nom']) ?></strong></a>
                        <?php if ($p['en_vedette']): ?> <span class="etiquette-admin">★ E preferuar</span><?php endif; ?>
                    </td>
                    <td><?= e($p['categorie']) ?></td>
                    <td>
                        <?php if ($p['prix_min'] === null): ?>
                            <span class="texte-rouge">Pa sasi</span>
                        <?php else: ?>
                            <?= (int) $p['nb_variantes'] > 1 ? 'nga ' : '' ?><?= prix($p['prix_min']) ?>
                            <?php if ($reduc): ?><br><small>-<?= $reduc ?>%</small><?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= (int) $p['stock_total'] ?> copë
                        <br><small><?= (int) $p['nb_variantes'] ?> sasi</small>
                    </td>
                    <td><?= $p['actif'] ? 'Po' : '<span class="texte-rouge">Jo (i fshehur)</span>' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

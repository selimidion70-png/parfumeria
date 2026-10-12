<?php
require __DIR__ . '/_menu.php';

// Une ligne de la liste : champ pour renommer + bouton "Fshi".
// $type = 'marque' ou 'note' ; $peutSupprimer = false pour une marque encore utilisée
$ligne = function (array $element, string $type, bool $peutSupprimer, string $confirmation): void {
    $id = (int) $element['id'];
    $nb = (int) $element['nb_produits'];
    ?>
    <li class="ligne-gestion">
        <form method="post" action="<?= url('admin_marques_notes') ?>" class="form-renommer">
            <?= csrf_champ() ?>
            <input type="hidden" name="action" value="<?= $type ?>_renommer">
            <input type="hidden" name="id" value="<?= $id ?>">
            <label class="invisible" for="<?= $type . $id ?>">Emri</label>
            <input type="text" id="<?= $type . $id ?>" name="nom" value="<?= e($element['nom']) ?>" required maxlength="<?= $type === 'marque' ? 100 : 60 ?>">
            <button type="submit" class="lien">Ruaj</button>
        </form>
        <small class="nb-parfums"><?= $nb ?> parfum<?= $nb === 1 ? '' : 'e' ?></small>
        <?php if ($peutSupprimer): ?>
            <form method="post" action="<?= url('admin_marques_notes') ?>" onsubmit="return confirm(<?= e(json_encode($confirmation)) ?>);">
                <?= csrf_champ() ?>
                <input type="hidden" name="action" value="<?= $type ?>_supprimer">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="lien lien-danger">Fshi</button>
            </form>
        <?php else: ?>
            <span class="note" title="Ndryshoni fillimisht markën e parfumeve që e përdorin">—</span>
        <?php endif; ?>
    </li>
    <?php
};
?>
<h1>Markat & notat</h1>

<div class="deux-colonnes">
    <section class="recap">
        <h2>Markat</h2>
        <p class="note">Marka e re krijohet automatikisht kur e shkruani te një produkt. Një markë mund të fshihet vetëm kur asnjë parfum nuk e përdor.</p>
        <?php if (empty($marques)): ?>
            <p class="vide">Ende asnjë markë.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($marques as $m): ?>
                    <?php $ligne($m, 'marque', (int) $m['nb_produits'] === 0, 'Ta fshij markën “' . $m['nom'] . '”?'); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="recap">
        <h2>Aromat (notat)</h2>
        <p class="note">Shfaqen te “Zgjidh sipas aromës” në ballinë dhe te filtrat e katalogut.</p>
        <ul>
            <?php foreach ($notes as $n): ?>
                <?php
                $nb = (int) $n['nb_produits'];
                $message = 'Ta fshij aromën “' . $n['nom'] . '”?' . ($nb ? ' Do të hiqet nga ' . $nb . ' parfum' . ($nb === 1 ? '' : 'e') . '.' : '');
                $ligne($n, 'note', true, $message);
                ?>
            <?php endforeach; ?>
        </ul>

        <h3>Shto aromë</h3>
        <form method="post" action="<?= url('admin_marques_notes') ?>" class="ligne-ajout">
            <?= csrf_champ() ?>
            <input type="hidden" name="action" value="note_ajouter">
            <div>
                <label for="nouvelle_note">Emri</label>
                <input type="text" id="nouvelle_note" name="nom" required maxlength="60" placeholder="p.sh. Musk">
            </div>
            <button type="submit" class="bouton bouton-secondaire">Shto</button>
        </form>
    </section>
</div>

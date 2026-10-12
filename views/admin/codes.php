<?php
require __DIR__ . '/_menu.php';
$erreur = fn(string $champ) => isset($erreurs[$champ]) ? '<span class="erreur-champ">' . e($erreurs[$champ]) . '</span>' : '';

// État d'un code, pour la colonne "Statusi"
$etat = function (array $c): string {
    if (!$c['actif']) return 'Joaktiv';
    if ($c['expire']) return 'Ka skaduar';
    if ($c['utilisations_max'] !== null && $c['utilisations'] >= $c['utilisations_max']) return 'I përdorur plotësisht';
    return 'Aktiv';
};
?>
<h1>Kodet e zbritjes</h1>
<p class="note">Klienti e shkruan kodin te shporta. Një porosi e anuluar e liron përdorimin e kodit.</p>

<?php if ($codes): ?>
    <div class="tableau-defilant">
        <table class="tableau tableau-compact">
            <thead>
                <tr>
                    <th scope="col">Kodi</th>
                    <th scope="col">Zbritja</th>
                    <th scope="col">Kushtet</th>
                    <th scope="col">Përdorur</th>
                    <th scope="col">Statusi</th>
                    <th scope="col"><span class="invisible">Veprimet</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($codes as $c): ?>
                    <?php $statut = $etat($c); ?>
                    <tr class="<?= $statut === 'Aktiv' ? '' : 'ligne-inactive' ?>">
                        <td><strong><?= e($c['code']) ?></strong></td>
                        <td><?= e(CodePromo::libelle($c)) ?></td>
                        <td>
                            <small>
                                <?= $c['minimum'] !== null ? 'Mbi ' . prix($c['minimum']) . '<br>' : '' ?>
                                <?= $c['expire_le'] ? 'Deri më ' . date('d.m.Y', strtotime($c['expire_le'])) : 'Pa afat' ?>
                            </small>
                        </td>
                        <td><?= (int) $c['utilisations'] ?><?= $c['utilisations_max'] !== null ? ' / ' . (int) $c['utilisations_max'] : '' ?></td>
                        <td><?= $statut ?></td>
                        <td class="cellule-actions">
                            <form method="post" action="<?= url('admin_codes') ?>">
                                <?= csrf_champ() ?>
                                <input type="hidden" name="action" value="basculer">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="lien"><?= $c['actif'] ? 'Çaktivizo' : 'Aktivizo' ?></button>
                            </form>
                            <form method="post" action="<?= url('admin_codes') ?>" onsubmit="return confirm(<?= e(json_encode('Ta fshij kodin ' . $c['code'] . '?')) ?>);">
                                <?= csrf_champ() ?>
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="lien lien-danger">Fshi</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="vide">Ende asnjë kod zbritjeje.</p>
<?php endif; ?>

<section class="recap espace-haut">
    <h2>Kod i ri</h2>
    <form method="post" action="<?= url('admin_codes') ?>" class="formulaire formulaire-code" novalidate>
        <?= csrf_champ() ?>
        <input type="hidden" name="action" value="creer">

        <div>
            <label for="code">Kodi</label>
            <input type="text" id="code" name="code" value="<?= e($donnees['code']) ?>" required maxlength="30" placeholder="VERA10" autocomplete="off">
            <?= $erreur('code') ?>
        </div>

        <div>
            <label for="type">Lloji</label>
            <select id="type" name="type">
                <option value="pourcentage" <?= $donnees['type'] === 'pourcentage' ? 'selected' : '' ?>>Përqindje (%)</option>
                <option value="montant" <?= $donnees['type'] === 'montant' ? 'selected' : '' ?>>Shumë fikse (€)</option>
            </select>
        </div>

        <div>
            <label for="valeur">Vlera</label>
            <input type="text" inputmode="decimal" id="valeur" name="valeur" value="<?= e($donnees['valeur']) ?>" required placeholder="10">
            <?= $erreur('valeur') ?>
        </div>

        <div>
            <label for="minimum">Porosia minimale (€, opsionale)</label>
            <input type="text" inputmode="decimal" id="minimum" name="minimum" value="<?= e($donnees['minimum']) ?>" placeholder="p.sh. 50">
            <?= $erreur('minimum') ?>
        </div>

        <div>
            <label for="expire_le">Vlen deri më (opsionale)</label>
            <input type="date" id="expire_le" name="expire_le" value="<?= e($donnees['expire_le']) ?>">
            <?= $erreur('expire_le') ?>
        </div>

        <div>
            <label for="utilisations_max">Sa herë mund të përdoret (opsionale)</label>
            <input type="number" id="utilisations_max" name="utilisations_max" value="<?= e($donnees['utilisations_max']) ?>" min="1" placeholder="pa kufi">
            <?= $erreur('utilisations_max') ?>
        </div>

        <button type="submit" class="bouton">Krijo kodin</button>
    </form>
</section>

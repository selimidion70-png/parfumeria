<h1>Shporta</h1>

<?php if (empty($lignes)): ?>
    <p class="vide">Shporta juaj është e zbrazët.</p>
    <p><a class="bouton" href="<?= url('home') ?>">Shiko dyqanin</a></p>
<?php else: ?>
    <div class="tableau-defilant">
        <table class="tableau tableau-panier">
            <thead>
                <tr>
                    <th scope="col">Artikulli</th>
                    <th scope="col">Çmimi</th>
                    <th scope="col">Sasia</th>
                    <th scope="col">Nëntotali</th>
                    <th scope="col"><span class="invisible">Veprimet</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $l): ?>
                    <tr>
                        <td>
                            <a href="<?= url('produit', ['id' => $l['produit_id']]) ?>"><?= e($l['nom']) ?></a><br>
                            <small><?= e(($l['marque'] ? $l['marque'] . ' · ' : '') . ml($l['contenance_ml'])) ?></small>
                        </td>
                        <td data-libelle="Çmimi"><?= prix($l['prix']) ?></td>
                        <td>
                            <form method="post" action="<?= url('panier_modifier') ?>" class="form-quantite">
                                <?= csrf_champ() ?>
                                <input type="hidden" name="variante_id" value="<?= $l['variante_id'] ?>">
                                <label class="invisible" for="q<?= $l['variante_id'] ?>">Sasia për <?= e($l['nom']) ?></label>
                                <input type="number" id="q<?= $l['variante_id'] ?>" name="quantite" value="<?= $l['quantite'] ?>" min="0" max="<?= $l['stock'] ?>">
                                <button type="submit" class="bouton bouton-secondaire">OK</button>
                            </form>
                        </td>
                        <td data-libelle="Nëntotali"><?= prix($l['sous_total']) ?></td>
                        <td>
                            <form method="post" action="<?= url('panier_supprimer') ?>">
                                <?= csrf_champ() ?>
                                <input type="hidden" name="variante_id" value="<?= $l['variante_id'] ?>">
                                <button type="submit" class="lien" aria-label="Hiq <?= e($l['nom']) ?> nga shporta">Hiq</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="total-panier">
        <!-- Code promo -->
        <form method="post" action="<?= url('panier_code') ?>" class="form-code">
            <?= csrf_champ() ?>
            <?php if ($promo['code']): ?>
                <p>
                    Kodi <strong><?= e($promo['code']['code']) ?></strong>
                    <?php if ($promo['erreur']): ?>
                        — <span class="texte-rouge"><?= e($promo['erreur']) ?></span>
                    <?php endif; ?>
                    <button type="submit" name="retirer" value="1" class="lien">Hiq kodin</button>
                </p>
            <?php else: ?>
                <label for="code">Keni një kod zbritjeje?</label>
                <div class="ligne-code">
                    <input type="text" id="code" name="code" maxlength="30" autocomplete="off" placeholder="p.sh. VERA10">
                    <button type="submit" class="bouton bouton-secondaire">Apliko</button>
                </div>
            <?php endif; ?>
        </form>

        <p>Nëntotali: <strong><?= prix($sousTotal) ?></strong></p>
        <?php if ($promo['remise'] > 0): ?>
            <p class="ligne-remise">Zbritja (<?= e(CodePromo::libelle($promo['code'])) ?>): <strong>-<?= prix($promo['remise']) ?></strong></p>
            <p>Totali: <strong><?= prix($sousTotal - $promo['remise']) ?></strong></p>
        <?php endif; ?>
        <a class="bouton" href="<?= url('commande') ?>">Vazhdo me porosinë</a>
    </div>

    <?php if (livraison_payante()): ?>
        <?php $manque = LIVRAISON_GRATUITE_DES - ($sousTotal - $promo['remise']); ?>
        <!-- Encourage à atteindre le seuil de la livraison gratuite -->
        <p class="info-livraison">
            <?php if ($manque <= 0): ?>
                ✓ Dërgesa juaj është <strong>falas</strong>!
            <?php else: ?>
                Shtoni edhe <strong><?= prix($manque) ?></strong> për dërgesë falas (mbi <?= prix(LIVRAISON_GRATUITE_DES) ?>).
            <?php endif; ?>
        </p>
    <?php endif; ?>
<?php endif; ?>

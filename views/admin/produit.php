<?php
require __DIR__ . '/_menu.php';
$erreur = fn(string $champ) => isset($erreurs[$champ]) ? '<span class="erreur-champ">' . e($erreurs[$champ]) . '</span>' : '';
// Prix affiché dans un champ : 49.90 -> "49.90", NULL -> ""
$champPrix = fn($valeur) => $valeur === null ? '' : number_format((float) $valeur, 2, '.', '');
?>
<p class="fil"><a href="<?= url('admin_produits') ?>">Produktet</a> / <?= $produit ? e($produit['nom']) : 'Produkt i ri' ?></p>

<div class="titre-ligne">
    <h1><?= $produit ? e($produit['nom']) : 'Produkt i ri' ?></h1>
    <?php if ($produit && $produit['actif'] && $variantes): ?>
        <a href="<?= url('produit', ['id' => $produit['id']]) ?>">Shiko në dyqan →</a>
    <?php endif; ?>
</div>

<div class="deux-colonnes">
    <!-- 1. Infos du parfum + photo -->
    <form method="post" action="<?= url('admin_produit', $produit ? ['id' => $produit['id']] : []) ?>" enctype="multipart/form-data" class="formulaire recap" novalidate>
        <?= csrf_champ() ?>
        <h2>Të dhënat</h2>

        <label for="marque">Marka</label>
        <input type="text" id="marque" name="marque" value="<?= e($donnees['marque']) ?>" maxlength="100" list="marques-existantes" placeholder="p.sh. Atelier Noir (bosh = pa markë)">
        <datalist id="marques-existantes">
            <?php foreach ($marques as $m): ?>
                <option value="<?= e($m['nom']) ?>">
            <?php endforeach; ?>
        </datalist>
        <span class="note">Shkruani një markë të re dhe ajo krijohet automatikisht.</span>
        <?= $erreur('marque') ?>

        <label for="nom">Emri i parfumit</label>
        <input type="text" id="nom" name="nom" value="<?= e($donnees['nom']) ?>" required maxlength="150">
        <?= $erreur('nom') ?>

        <label for="categorie_id">Për kë</label>
        <select id="categorie_id" name="categorie_id" required>
            <option value="">Zgjidhni…</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= (int) $donnees['categorie_id'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['nom']) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $erreur('categorie_id') ?>

        <fieldset class="cases-notes">
            <legend>Aroma (notat)</legend>
            <?php foreach ($notes as $n): ?>
                <label class="case"><input type="checkbox" name="notes[]" value="<?= (int) $n['id'] ?>" <?= in_array((int) $n['id'], array_map('intval', $donnees['notes']), true) ? 'checked' : '' ?>> <?= e($n['nom']) ?></label>
            <?php endforeach; ?>
        </fieldset>

        <label for="description">Përshkrimi</label>
        <textarea id="description" name="description" rows="4"><?= e($donnees['description']) ?></textarea>

        <label for="image">Fotoja kryesore (JPG, PNG ose WEBP, deri në 3 MB)</label>
        <?php if (!empty($produit['image'])): ?>
            <img class="apercu" src="public/img/produits/<?= e($produit['image']) ?>" alt="Fotoja aktuale">
            <label class="case"><input type="checkbox" name="supprimer_image" value="1"> Fshi foton</label>
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <?= $erreur('image') ?>

        <label class="case"><input type="checkbox" name="actif" value="1" <?= $donnees['actif'] ? 'checked' : '' ?>> I dukshëm në dyqan</label>
        <label class="case"><input type="checkbox" name="en_vedette" value="1" <?= $donnees['en_vedette'] ? 'checked' : '' ?>> Shfaqe te “Më të preferuarat” në ballinë</label>

        <button type="submit" class="bouton"><?= $produit ? 'Ruaj' : 'Krijo produktin' ?></button>
    </form>

    <!-- 2. Contenances : seulement une fois le produit créé -->
    <section class="recap">
        <h2>Sasitë, çmimet dhe stoku</h2>

        <?php if (!$produit): ?>
            <p class="note">Krijoni fillimisht produktin, pastaj shtoni sasitë (30 ml, 50 ml, 100 ml…) me çmimet e tyre.</p>
        <?php else: ?>
            <?php if (empty($variantes)): ?>
                <p class="vide">Ende asnjë sasi: produkti nuk shfaqet në dyqan derisa të shtoni të paktën një.</p>
            <?php else: ?>
                <p class="note">“Çmimi i vjetër” = për zbritje. Lëreni bosh nëse nuk ka zbritje.</p>
                <form method="post" action="<?= url('admin_variantes') ?>">
                    <?= csrf_champ() ?>
                    <input type="hidden" name="produit_id" value="<?= (int) $produit['id'] ?>">
                    <div class="tableau-defilant">
                        <table class="tableau tableau-compact">
                            <thead>
                                <tr>
                                    <th scope="col">Sasia</th>
                                    <th scope="col">Çmimi (€)</th>
                                    <th scope="col">Çmimi i vjetër</th>
                                    <th scope="col">Stoku</th>
                                    <th scope="col"><span class="invisible">Veprimet</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($variantes as $v): ?>
                                    <?php $id = (int) $v['id']; $libelle = ml($v['contenance_ml']); ?>
                                    <tr>
                                        <td><strong><?= $libelle ?></strong></td>
                                        <td>
                                            <label class="invisible" for="prix<?= $id ?>">Çmimi për <?= $libelle ?></label>
                                            <input type="text" inputmode="decimal" id="prix<?= $id ?>" name="prix[<?= $id ?>]" value="<?= $champPrix($v['prix']) ?>" class="champ-stock" required>
                                        </td>
                                        <td>
                                            <label class="invisible" for="ancien<?= $id ?>">Çmimi i vjetër për <?= $libelle ?></label>
                                            <input type="text" inputmode="decimal" id="ancien<?= $id ?>" name="prix_ancien[<?= $id ?>]" value="<?= $champPrix($v['prix_ancien']) ?>" class="champ-stock" placeholder="—">
                                        </td>
                                        <td>
                                            <label class="invisible" for="stock<?= $id ?>">Stoku për <?= $libelle ?></label>
                                            <input type="number" id="stock<?= $id ?>" name="stock[<?= $id ?>]" value="<?= (int) $v['stock'] ?>" min="0" class="champ-stock">
                                        </td>
                                        <td>
                                            <!-- form="..." : le bouton envoie le formulaire de suppression placé plus bas (on ne peut pas imbriquer deux <form>) -->
                                            <button type="submit" form="suppr<?= $id ?>" class="lien" aria-label="Fshi <?= $libelle ?>">Fshi</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="bouton bouton-secondaire espace-haut">Ruaj çmimet dhe stokun</button>
                </form>

                <?php foreach ($variantes as $v): ?>
                    <form id="suppr<?= (int) $v['id'] ?>" method="post" action="<?= url('admin_variante_supprimer') ?>" hidden
                          onsubmit="return confirm(<?= e(json_encode('Ta fshij ' . ml($v['contenance_ml']) . '?')) ?>);">
                        <?= csrf_champ() ?>
                        <input type="hidden" name="produit_id" value="<?= (int) $produit['id'] ?>">
                        <input type="hidden" name="variante_id" value="<?= (int) $v['id'] ?>">
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>

            <h3>Shto sasi</h3>
            <form method="post" action="<?= url('admin_variante_ajouter') ?>" class="ligne-ajout">
                <?= csrf_champ() ?>
                <input type="hidden" name="produit_id" value="<?= (int) $produit['id'] ?>">
                <div>
                    <label for="contenance_ml">Sasia (ml)</label>
                    <input type="number" id="contenance_ml" name="contenance_ml" min="1" max="1000" required placeholder="100" list="contenances-courantes" class="champ-stock">
                    <datalist id="contenances-courantes">
                        <?php foreach ([10, 30, 50, 75, 100, 125, 150, 200] as $c): ?><option value="<?= $c ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label for="prix_nouveau">Çmimi (€)</label>
                    <input type="text" inputmode="decimal" id="prix_nouveau" name="prix" required placeholder="49.90" class="champ-stock">
                </div>
                <div>
                    <label for="ancien_nouveau">Çmimi i vjetër</label>
                    <input type="text" inputmode="decimal" id="ancien_nouveau" name="prix_ancien" placeholder="opsional" class="champ-stock">
                </div>
                <div>
                    <label for="stock_nouveau">Stoku</label>
                    <input type="number" id="stock_nouveau" name="stock" value="0" min="0" class="champ-stock">
                </div>
                <button type="submit" class="bouton bouton-secondaire">Shto</button>
            </form>
        <?php endif; ?>
    </section>
</div>

<!-- 3. Photos supplémentaires : seulement une fois le produit créé -->
<?php if ($produit): ?>
    <section class="recap espace-haut">
        <h2>Fotot shtesë</h2>
        <p class="note">Shfaqen në faqen e parfumit, pas fotos kryesore (p.sh. kutia, shishja nga afër). Maksimumi <?= (int) $photosMax ?> foto.</p>

        <?php if ($images): ?>
            <ul class="galerie-admin">
                <?php foreach ($images as $img): ?>
                    <li>
                        <img src="public/img/produits/<?= e($img['fichier']) ?>" alt="" loading="lazy">
                        <form method="post" action="<?= url('admin_photo_principale') ?>">
                            <?= csrf_champ() ?>
                            <input type="hidden" name="produit_id" value="<?= (int) $produit['id'] ?>">
                            <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                            <button type="submit" class="lien">Bëje kryesore</button>
                        </form>
                        <form method="post" action="<?= url('admin_photo_supprimer') ?>" onsubmit="return confirm('Ta fshij këtë foto?');">
                            <?= csrf_champ() ?>
                            <input type="hidden" name="produit_id" value="<?= (int) $produit['id'] ?>">
                            <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                            <button type="submit" class="lien lien-danger">Fshi</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (count($images) < $photosMax): ?>
            <form method="post" action="<?= url('admin_photos_ajouter') ?>" enctype="multipart/form-data" class="ligne-ajout">
                <?= csrf_champ() ?>
                <input type="hidden" name="produit_id" value="<?= (int) $produit['id'] ?>">
                <div>
                    <label for="photos">Zgjidhni një ose më shumë foto</label>
                    <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
                </div>
                <button type="submit" class="bouton bouton-secondaire">Shto fotot</button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

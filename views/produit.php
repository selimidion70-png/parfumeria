<?php
$enStock = array_filter($variantes, fn($v) => $v['stock'] > 0);
// Contenance cochée par défaut : la première disponible
$premiereDispo = $enStock ? (int) array_values($enStock)[0]['id'] : 0;

// Toutes les photos : la principale d'abord, puis les supplémentaires
$photos = array_merge(
    $produit['image'] ? [$produit['image']] : [],
    array_column($images, 'fichier')
);
$nomComplet = trim(($produit['marque'] ?? '') . ' ' . $produit['nom']);
?>
<p class="fil">
    <a href="<?= url('home') ?>">Ballina</a> /
    <a href="<?= url('produits', ['categorie' => $produit['categorie_slug']]) ?>"><?= e($produit['categorie']) ?></a> /
    <?= e($produit['nom']) ?>
</p>

<article class="fiche">
    <div class="fiche-galerie">
        <div class="fiche-visuel">
            <?php if ($photos): ?>
                <img id="photo-grande" src="public/img/produits/<?= e($photos[0]) ?>" alt="<?= e($nomComplet) ?>">
            <?php else: ?>
                <?php require __DIR__ . '/partials/flacon.php'; ?>
            <?php endif; ?>
        </div>

        <?php if (count($photos) > 1): ?>
            <!-- Miniatures : un clic affiche la photo en grand (sans JavaScript, le lien ouvre simplement la photo) -->
            <ul class="vignettes">
                <?php foreach ($photos as $i => $photo): ?>
                    <li>
                        <a class="vignette" href="public/img/produits/<?= e($photo) ?>" <?= $i === 0 ? 'aria-current="true"' : '' ?>>
                            <img src="public/img/produits/<?= e($photo) ?>" alt="<?= e($nomComplet) ?> — fotoja <?= $i + 1 ?>" loading="lazy">
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <script>
                // Clic sur une miniature : on change la grande photo au lieu d'ouvrir le fichier
                document.querySelectorAll('.vignette').forEach(function (lien) {
                    lien.addEventListener('click', function (evenement) {
                        evenement.preventDefault();
                        document.getElementById('photo-grande').src = lien.href;
                        document.querySelectorAll('.vignette').forEach(function (autre) { autre.removeAttribute('aria-current'); });
                        lien.setAttribute('aria-current', 'true');
                    });
                });
            </script>
        <?php endif; ?>
    </div>

    <div class="fiche-infos">
        <?php if ($produit['marque']): ?>
            <a class="fiche-marque" href="<?= url('produits', ['marque' => $produit['marque_slug']]) ?>"><?= e($produit['marque']) ?></a>
        <?php endif; ?>
        <h1><?= e($produit['nom']) ?></h1>
        <p class="note">Parfum për <?= e(mb_strtolower($produit['categorie'])) ?></p>

        <?php if ($notes): ?>
            <ul class="puces-notes puces-petites">
                <?php foreach ($notes as $n): ?>
                    <li><a class="puce" href="<?= url('produits', ['note' => $n['slug']]) ?>"><?= e($n['nom']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (empty($enStock)): ?>
            <p class="message message-erreur">Ky parfum është shitur. Na kontaktoni për ta porositur.</p>
        <?php else: ?>
            <form method="post" action="<?= url('panier_ajouter') ?>" class="form-achat">
                <?= csrf_champ() ?>

                <fieldset>
                    <legend>Zgjidhni sasinë</legend>
                    <div class="choix-contenance">
                        <?php foreach ($variantes as $v): ?>
                            <?php $reduc = reduction($v['prix'], $v['prix_ancien']); ?>
                            <label class="contenance <?= $v['stock'] <= 0 ? 'contenance-epuisee' : '' ?>">
                                <input type="radio" name="variante_id" value="<?= (int) $v['id'] ?>" required
                                    <?= $v['stock'] <= 0 ? 'disabled' : '' ?> <?= (int) $v['id'] === $premiereDispo ? 'checked' : '' ?>>
                                <span class="contenance-ml"><?= ml($v['contenance_ml']) ?></span>
                                <span class="contenance-prix">
                                    <strong class="<?= $reduc ? 'prix-promo' : '' ?>"><?= prix($v['prix']) ?></strong>
                                    <?php if ($reduc): ?><s><?= prix($v['prix_ancien']) ?></s> <span class="badge badge-promo badge-inline">-<?= $reduc ?>%</span><?php endif; ?>
                                </span>
                                <small>
                                    <?php if ($v['stock'] <= 0): ?>I shitur
                                    <?php elseif ($v['stock'] <= 3): ?>Vetëm <?= (int) $v['stock'] ?> copë
                                    <?php else: ?>Në stok<?php endif; ?>
                                </small>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="ligne-achat">
                    <label class="invisible" for="quantite">Sasia</label>
                    <input type="number" id="quantite" name="quantite" value="1" min="1" max="10" required>
                    <button type="submit" class="bouton">Shto në shportë</button>
                </div>
            </form>
        <?php endif; ?>

        <?= bouton_favori((int) $produit['id'], 'coeur-fiche') ?>

        <?php if (CONTACT_TELEPHONE): ?>
            <?php
            // Message déjà écrit dans WhatsApp : le cousin sait tout de suite de quel parfum on parle
            $messageWhatsapp = 'Përshëndetje! Dëshiroj të porosis parfumin '
                . trim(($produit['marque'] ?? '') . ' ' . $produit['nom']) . ': '
                . url_absolue('produit', ['id' => $produit['id']]);
            ?>
            <div class="commande-messagerie">
                <p>Preferoni të porosisni me mesazh?</p>
                <div class="commande-messagerie-boutons">
                    <a class="bouton bouton-whatsapp" href="<?= e(lien_whatsapp($messageWhatsapp)) ?>" target="_blank" rel="noopener">Porosit në WhatsApp</a>
                    <a class="bouton bouton-viber" href="<?= e(lien_viber()) ?>">Viber</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($produit['description']): ?>
            <div class="fiche-description">
                <h2>Përshkrimi</h2>
                <p><?= nl2br(e($produit['description'])) ?></p>
            </div>
        <?php endif; ?>

        <ul class="fiche-garanties">
            <li>Pagesa në dorëzim — paguani kur ta merrni</li>
            <li>Dërgesë në Kosovë, Shqipëri dhe Maqedoninë e Veriut</li>
            <li><a href="<?= url('retours') ?>">Politika e kthimit</a></li>
        </ul>
    </div>
</article>

<?php if ($similaires): ?>
    <section class="section section-similaires">
        <div class="section-entete">
            <h2>Mund t'ju pëlqejnë edhe</h2>
        </div>
        <div class="grille">
            <?php foreach ($similaires as $p): ?>
                <?php require __DIR__ . '/partials/carte.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

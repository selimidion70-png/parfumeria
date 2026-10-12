<?php if (!$pleineLargeur): ?></div><?php endif; ?>
</main>

<footer class="pied">
    <div class="conteneur pied-colonnes">
        <div>
            <p class="pied-logo"><?= NOM_SITE ?></p>
            <p>Parfume për meshkuj, femra dhe unisex. Dërgesë në <?= e(implode(', ', array_keys(PAYS_LIVRAISON))) ?>. Pagesa bëhet në dorëzim.</p>
        </div>

        <nav aria-label="Dyqani">
            <h2>Dyqani</h2>
            <ul>
                <?php foreach ($categoriesMenu as $cat): ?>
                    <li><a href="<?= url('produits', ['categorie' => $cat['slug']]) ?>"><?= e($cat['nom']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= url('produits', ['promo' => 1]) ?>">Në zbritje</a></li>
                <li><a href="<?= url('produits') ?>">Të gjitha parfumet</a></li>
            </ul>
        </nav>

        <nav aria-label="Për klientët">
            <h2>Për klientët</h2>
            <ul>
                <li><a href="<?= url('contact') ?>">Kontakti</a></li>
                <li><a href="<?= url('livraison') ?>">Pagesa dhe dërgesa</a></li>
                <li><a href="<?= url('retours') ?>">Politika e kthimit</a></li>
                <li><a href="<?= url('confidentialite') ?>">Politika e privatësisë</a></li>
            </ul>
        </nav>

        <div>
            <h2>Na ndiqni</h2>
            <?php if (LIEN_INSTAGRAM || LIEN_FACEBOOK || LIEN_TIKTOK): ?>
                <ul>
                    <?php if (LIEN_INSTAGRAM): ?><li><a href="<?= e(LIEN_INSTAGRAM) ?>" rel="noopener" target="_blank">Instagram</a></li><?php endif; ?>
                    <?php if (LIEN_FACEBOOK): ?><li><a href="<?= e(LIEN_FACEBOOK) ?>" rel="noopener" target="_blank">Facebook</a></li><?php endif; ?>
                    <?php if (LIEN_TIKTOK): ?><li><a href="<?= e(LIEN_TIKTOK) ?>" rel="noopener" target="_blank">TikTok</a></li><?php endif; ?>
                </ul>
            <?php else: ?>
                <p>Së shpejti në rrjetet sociale.</p>
            <?php endif; ?>
            <?php if (CONTACT_TELEPHONE): ?>
                <p><a href="tel:+<?= e(telephone_chiffres()) ?>"><?= e(CONTACT_TELEPHONE) ?></a></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="conteneur pied-bas">
        <p>© <?= date('Y') ?> <?= NOM_SITE ?>. Të gjitha të drejtat e rezervuara.</p>
    </div>
</footer>

<?php
// Bouton WhatsApp flottant en bas à droite. Pas sur l'admin, ni sur les pages où il cacherait
// des boutons importants : la fiche produit (elle a déjà son bouton WhatsApp), le panier et la commande.
$pageActuelle = is_string($_GET['page'] ?? null) ? $_GET['page'] : '';
$sansBoutonFlottant = in_array($pageActuelle, ['produit', 'panier', 'commande'], true) || str_starts_with($pageActuelle, 'admin');
?>
<?php if (CONTACT_TELEPHONE && !$sansBoutonFlottant): ?>
    <a class="whatsapp-flottant" href="<?= e(lien_whatsapp('Përshëndetje! Kam një pyetje.')) ?>" target="_blank" rel="noopener" aria-label="Na shkruani në WhatsApp">
        <!-- Icône bulle de discussion avec un combiné de téléphone -->
        <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true">
            <path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1-.7-.3-1.4-.7-2-1.3-.5-.5-1-1.1-1.3-1.7-.1-.2 0-.4.1-.5l.4-.5.3-.4v-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.6.6-.9 1.4-.9 2.2.1 1 .5 2 1.1 2.8 1.1 1.7 2.6 3 4.4 3.8.5.2 1 .4 1.5.5.6.2 1.2.1 1.7-.1.6-.3 1-.8 1.1-1.4.1-.3.1-.6 0-.8l-.6-.3z"/>
        </svg>
    </a>
<?php endif; ?>

</body>
</html>

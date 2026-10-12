<div class="page-texte">
    <h1>Kontakti</h1>
    <p>Keni një pyetje për një parfum, për porosinë tuaj apo për dërgesën? Na shkruani, ju përgjigjemi sa më shpejt.</p>

    <?php if (CONTACT_TELEPHONE || CONTACT_EMAIL || LIEN_INSTAGRAM || LIEN_FACEBOOK || LIEN_TIKTOK): ?>
        <ul class="liste-contact">
            <?php if (CONTACT_TELEPHONE): ?>
                <li><strong>Telefoni:</strong> <a href="tel:+<?= e(telephone_chiffres()) ?>"><?= e(CONTACT_TELEPHONE) ?></a></li>
                <li><strong>WhatsApp:</strong> <a href="<?= e(lien_whatsapp()) ?>" rel="noopener" target="_blank">Na shkruani në WhatsApp</a></li>
                <li><strong>Viber:</strong> <a href="<?= e(lien_viber()) ?>">Na shkruani në Viber</a></li>
            <?php endif; ?>
            <?php if (CONTACT_EMAIL): ?>
                <li><strong>Email:</strong> <a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></li>
            <?php endif; ?>
            <?php if (LIEN_INSTAGRAM): ?><li><strong>Instagram:</strong> <a href="<?= e(LIEN_INSTAGRAM) ?>" rel="noopener" target="_blank">Na ndiqni</a></li><?php endif; ?>
            <?php if (LIEN_FACEBOOK): ?><li><strong>Facebook:</strong> <a href="<?= e(LIEN_FACEBOOK) ?>" rel="noopener" target="_blank">Na ndiqni</a></li><?php endif; ?>
            <?php if (LIEN_TIKTOK): ?><li><strong>TikTok:</strong> <a href="<?= e(LIEN_TIKTOK) ?>" rel="noopener" target="_blank">Na ndiqni</a></li><?php endif; ?>
        </ul>
    <?php else: ?>
        <!-- TODO : remplir CONTACT_TELEPHONE, CONTACT_EMAIL et les liens dans config/app.php -->
        <p class="message message-erreur">Të dhënat e kontaktit do të shtohen së shpejti.</p>
    <?php endif; ?>

    <p>Për porositë: pasi të bëni porosinë, ju telefonojmë në numrin që keni dhënë për ta konfirmuar.</p>
</div>

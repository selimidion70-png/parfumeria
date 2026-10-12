<div class="page-etroite">
    <h1>Keni harruar fjalëkalimin?</h1>

    <p>Shkruani emailin e llogarisë suaj. Do t'ju dërgojmë një lidhje për të zgjedhur një fjalëkalim të ri.</p>

    <form method="post" action="<?= url('mot_de_passe_oublie') ?>" class="formulaire">
        <?= csrf_champ() ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email">

        <button type="submit" class="bouton">Dërgo lidhjen</button>
    </form>

    <p class="note"><a href="<?= url('connexion') ?>">Kthehu te kyçja</a></p>
</div>

<?php
$erreur = fn(string $champ) => isset($erreurs[$champ]) ? '<span class="erreur-champ">' . e($erreurs[$champ]) . '</span>' : '';
?>
<div class="page-etroite">
    <h1>Zgjidhni një fjalëkalim të ri</h1>

    <form method="post" action="<?= url('nouveau_mot_de_passe') ?>" class="formulaire" novalidate>
        <?= csrf_champ() ?>
        <!-- Le jeton du lien reçu par email, renvoyé avec le formulaire -->
        <input type="hidden" name="jeton" value="<?= e($jeton) ?>">

        <label for="mot_de_passe">Fjalëkalimi i ri (së paku 8 karaktere)</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="new-password">
        <?= $erreur('mot_de_passe') ?>

        <label for="confirmation">Përsërit fjalëkalimin</label>
        <input type="password" id="confirmation" name="confirmation" required autocomplete="new-password">
        <?= $erreur('confirmation') ?>

        <button type="submit" class="bouton">Ruaj fjalëkalimin</button>
    </form>
</div>

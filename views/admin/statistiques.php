<?php
require __DIR__ . '/_menu.php';

$nomsMois = [1 => 'Janar', 'Shkurt', 'Mars', 'Prill', 'Maj', 'Qershor', 'Korrik', 'Gusht', 'Shtator', 'Tetor', 'Nëntor', 'Dhjetor'];
// "2026-10" -> "Tetor 2026"
$nomMois = fn(string $mois) => $nomsMois[(int) substr($mois, 5, 2)] . ' ' . substr($mois, 0, 4);

// Longueur des barres : le plus gros mois = 100 %
$valeurMax = max(array_column($parMois, 'valeur')) ?: 1;
$valeurMaxPays = max(array_column($parPays, 'valeur')) ?: 1;
?>
<h1>Statistikat</h1>
<p class="note">Porositë e anuluara nuk llogariten.</p>

<ul class="chiffres">
    <li><div><span class="chiffre"><?= $resume['commandes'] ?></span><span>Porosi gjithsej</span></div></li>
    <li><div><span class="chiffre"><?= prix($resume['panier_moyen']) ?></span><span>Vlera mesatare e porosisë</span></div></li>
    <li><div><span class="chiffre"><?= $resume['taux_annulation'] ?> %</span><span>Porosi të anuluara</span></div></li>
</ul>

<section class="recap">
    <h2>Shitjet sipas muajve</h2>
    <p class="note">“Vlera” = të gjitha porositë e muajit · “Të arkëtuara” = vetëm porositë e dorëzuara.</p>
    <div class="tableau-defilant">
        <table class="tableau tableau-compact tableau-stats">
            <thead>
                <tr>
                    <th scope="col">Muaji</th>
                    <th scope="col">Porositë</th>
                    <th scope="col">Vlera</th>
                    <th scope="col">Të arkëtuara</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parMois as $m): ?>
                    <tr>
                        <th scope="row"><?= e($nomMois($m['mois'])) ?></th>
                        <td><?= $m['nb'] ?></td>
                        <td>
                            <!-- Barre proportionnelle : le chiffre exact est écrit à côté -->
                            <div class="cellule-barre">
                                <span class="piste" aria-hidden="true"><?php if ($m['valeur'] > 0): ?><span class="barre" style="width: <?= round($m['valeur'] / $valeurMax * 100, 1) ?>%"></span><?php endif; ?></span>
                                <span><?= prix($m['valeur']) ?></span>
                            </div>
                        </td>
                        <td><?= prix($m['encaisse']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="deux-colonnes espace-haut">
    <section class="recap">
        <h2>Parfumet më të shitura</h2>
        <?php if (empty($plusVendus)): ?>
            <p class="vide">Ende asnjë shitje.</p>
        <?php else: ?>
            <ol class="liste-classement">
                <?php foreach ($plusVendus as $p): ?>
                    <li>
                        <span>
                            <?= e($p['nom_produit']) ?><br>
                            <?php if ($p['marque']): ?><small><?= e($p['marque']) ?></small><?php endif; ?>
                        </span>
                        <span class="texte-droite">
                            <strong><?= (int) $p['quantite'] ?> copë</strong><br>
                            <small><?= prix($p['montant']) ?></small>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>

    <section class="recap">
        <h2>Porositë sipas shtetit</h2>
        <ul>
            <?php foreach ($parPays as $pays => $s): ?>
                <li class="ligne-pays">
                    <span><?= e($pays) ?></span>
                    <span class="texte-droite"><strong><?= $s['nb'] ?> porosi</strong><br><small><?= prix($s['valeur']) ?></small></span>
                    <?php if ($s['valeur'] > 0): ?><span class="barre barre-pays" style="width: <?= round($s['valeur'] / $valeurMaxPays * 100, 1) ?>%" aria-hidden="true"></span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

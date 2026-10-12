<?php
// Bon de livraison ("fletëdërgesë") : page autonome, sans le menu du site.
// Variables : $commande, $details, $sousTotal
// total = articles - remise + livraison  =>  livraison = total - articles + remise
// round() : les calculs avec des décimales donnent parfois 0.0000000000000036 au lieu de 0
$fraisLivraison = round((float) $commande['total'] - $sousTotal + (float) $commande['remise'], 2);
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fletëdërgesa nr. <?= (int) $commande['id'] ?> — <?= e(NOM_SITE) ?></title>
    <style>
        /* Noir et blanc : lisible sur n'importe quelle imprimante, même sans couleur */
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #eee; color: #000; font: 15px/1.45 Arial, Helvetica, sans-serif; }
        .feuille { max-width: 780px; margin: 0 auto; padding: 40px; background: #fff; }
        .actions { max-width: 780px; margin: 0 auto 16px; display: flex; gap: 12px; }
        .actions button, .actions a { padding: 10px 20px; border: 1px solid #000; border-radius: 6px; background: #fff; color: #000; font: inherit; text-decoration: none; cursor: pointer; }
        .actions button { background: #000; color: #fff; }
        .entete { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 16px; border-bottom: 2px solid #000; }
        .boutique { font-size: 24px; font-weight: bold; }
        h1 { margin: 0; font-size: 20px; text-align: right; }
        .annulee { margin: 16px 0 0; padding: 10px; border: 3px solid #000; font-weight: bold; text-align: center; text-transform: uppercase; }
        .destinataire { margin: 24px 0; padding: 16px 20px; border: 2px solid #000; border-radius: 6px; font-size: 18px; }
        .destinataire small { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .telephone { font-size: 22px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #999; text-align: left; vertical-align: top; }
        th { border-bottom: 2px solid #000; font-size: 12px; text-transform: uppercase; }
        .nombre { text-align: right; white-space: nowrap; }
        .totaux td { border: 0; padding: 4px 6px; }
        .a-payer td { padding-top: 12px; font-size: 22px; font-weight: bold; }
        .signature { display: flex; justify-content: space-between; gap: 40px; margin-top: 56px; }
        .signature div { flex: 1; padding-top: 6px; border-top: 1px solid #000; font-size: 13px; }
        .pied { margin-top: 32px; font-size: 12px; text-align: center; }

        @media print {
            @page { size: A4; margin: 12mm; }
            body { padding: 0; background: #fff; }
            .feuille { max-width: none; padding: 0; }
            .actions { display: none; } /* les boutons ne s'impriment pas */
        }
    </style>
</head>
<body>

<div class="actions">
    <button type="button" onclick="window.print()">Printo</button>
    <a href="<?= url('admin_commande', ['id' => $commande['id']]) ?>">← Kthehu te porosia</a>
</div>

<div class="feuille">
    <div class="entete">
        <div>
            <div class="boutique"><?= e(NOM_SITE) ?></div>
            <?php if (CONTACT_TELEPHONE): ?><div><?= e(CONTACT_TELEPHONE) ?></div><?php endif; ?>
            <?php if (CONTACT_EMAIL): ?><div><?= e(CONTACT_EMAIL) ?></div><?php endif; ?>
        </div>
        <div>
            <h1>Fletëdërgesa<br>Porosia nr. <?= (int) $commande['id'] ?></h1>
            <div style="text-align: right">Data: <?= date('d.m.Y', strtotime($commande['created_at'])) ?></div>
        </div>
    </div>

    <?php if ($commande['statut'] === 'annulee'): ?>
        <p class="annulee">Kjo porosi është anuluar — mos e dërgoni</p>
    <?php endif; ?>

    <div class="destinataire">
        <small>Marrësi</small>
        <strong><?= e($commande['prenom'] . ' ' . $commande['nom']) ?></strong><br>
        <span class="telephone"><?= e($commande['telephone']) ?></span><br>
        <?= e($commande['adresse']) ?><br>
        <?= e($commande['ville']) ?>, <?= e($commande['pays']) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th scope="col">Sasia</th>
                <th scope="col">Artikulli</th>
                <th scope="col" class="nombre">Çmimi</th>
                <th scope="col" class="nombre">Totali</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $d): ?>
                <tr>
                    <td><?= (int) $d['quantite'] ?> ×</td>
                    <td>
                        <?= e(trim(($d['marque'] ?? '') . ' ' . $d['nom_produit'])) ?>
                        — <?= ml($d['contenance_ml']) ?>
                    </td>
                    <td class="nombre"><?= prix($d['prix_unitaire']) ?></td>
                    <td class="nombre"><?= prix($d['prix_unitaire'] * $d['quantite']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tbody class="totaux">
            <tr><td colspan="3" class="nombre">Nëntotali</td><td class="nombre"><?= prix($sousTotal) ?></td></tr>
            <?php if ($commande['remise'] > 0): ?>
                <tr><td colspan="3" class="nombre">Zbritja (<?= e($commande['code_promo']) ?>)</td><td class="nombre">-<?= prix($commande['remise']) ?></td></tr>
            <?php endif; ?>
            <tr><td colspan="3" class="nombre">Dërgesa</td><td class="nombre"><?= $fraisLivraison > 0 ? prix($fraisLivraison) : 'Falas' ?></td></tr>
            <tr class="a-payer"><td colspan="3" class="nombre">Për t'u paguar në dorëzim</td><td class="nombre"><?= prix($commande['total']) ?></td></tr>
        </tbody>
    </table>

    <div class="signature">
        <div>Nënshkrimi i dërguesit</div>
        <div>Nënshkrimi i marrësit</div>
    </div>

    <p class="pied">Faleminderit për porosinë!</p>
</div>

</body>
</html>

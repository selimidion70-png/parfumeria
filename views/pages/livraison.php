<!-- TODO : faire relire ce texte par le cousin (délais réels, transporteur) -->
<div class="page-texte">
    <h1>Pagesa dhe dërgesa</h1>

    <h2>Pagesa</h2>
    <p>Pagesa bëhet <strong>vetëm në dorëzim</strong>: paguani me para në dorë postierit kur ta merrni porosinë. Nuk ka pagesë online dhe nuk ju kërkojmë kurrë të dhënat e kartës bankare.</p>

    <h2>Ku dërgojmë</h2>
    <table class="tableau tableau-compact">
        <thead>
            <tr><th scope="col">Shteti</th><th scope="col">Kostoja e dërgesës</th></tr>
        </thead>
        <tbody>
            <?php foreach (PAYS_LIVRAISON as $pays => $frais): ?>
                <tr><td><?= e($pays) ?></td><td><?= $frais > 0 ? prix($frais) : 'Falas' ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Si funksionon</h2>
    <ol>
        <li>Bëni porosinë në faqe (nuk keni nevojë për llogari).</li>
        <li>Ju telefonojmë për ta konfirmuar porosinë.</li>
        <li>E dërgojmë pakon. Zakonisht arrin brenda pak ditëve pune.</li>
        <li>Paguani kur ta merrni.</li>
    </ol>
    <p>Nëse nuk jeni në shtëpi, postieri ju telefonon për të caktuar një kohë tjetër.</p>
</div>

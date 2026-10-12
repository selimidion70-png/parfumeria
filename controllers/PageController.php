<?php

// Pages d'information (contact, retours, livraison, confidentialité)
class PageController
{
    // Nom de la page dans l'URL => [fichier de la vue, titre de l'onglet]
    private const PAGES = [
        'contact'         => ['pages/contact', 'Kontakti'],
        'retours'         => ['pages/retours', 'Politika e kthimit'],
        'livraison'       => ['pages/livraison', 'Pagesa dhe dërgesa'],
        'confidentialite' => ['pages/confidentialite', 'Politika e privatësisë'],
    ];

    public function afficher(string $page): void
    {
        [$vue, $titre] = self::PAGES[$page];
        render($vue, ['titre' => $titre]);
    }
}

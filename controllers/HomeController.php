<?php

class HomeController
{
    // Page d'accueil : grande bannière + plusieurs sections de produits
    public function index(): void
    {
        // Une section par catégorie (Meshkuj, Femra, Unisex), 4 produits chacune
        $sectionsCategories = [];
        foreach (Categorie::toutes() as $categorie) {
            $produits = Produit::catalogue(['categorie_id' => $categorie['id'], 'limite' => 4]);
            if ($produits) {
                $sectionsCategories[] = ['categorie' => $categorie, 'produits' => $produits];
            }
        }

        render('home', [
            'titre'              => null, // null = juste le nom du site dans l'onglet
            'vedettes'           => Produit::catalogue(['vedette' => true, 'limite' => 8]),
            'nouveautes'         => Produit::catalogue(['tri' => 'nouveau', 'limite' => 8]),
            'promos'             => Produit::catalogue(['promo' => true, 'limite' => 4]),
            'sectionsCategories' => $sectionsCategories,
            'notes'              => Note::toutes(),
            'marques'            => Marque::avecProduits(),
        ]);
    }
}

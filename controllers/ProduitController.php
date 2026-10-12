<?php

class ProduitController
{
    // Catalogue : index.php?page=produits&categorie=femra&marque=velours&note=vanilje&q=rose&tri=prix_asc&promo=1
    public function catalogue(): void
    {
        $categorie = ($slug = $this->parametre('categorie')) ? Categorie::trouverParSlug($slug) : null;
        $marque    = ($slug = $this->parametre('marque')) ? Marque::trouverParSlug($slug) : null;
        $note      = ($slug = $this->parametre('note')) ? Note::trouverParSlug($slug) : null;
        $recherche = mb_substr($this->parametre('q'), 0, 100);
        $promo     = $this->parametre('promo') === '1';
        $tri       = array_key_exists($this->parametre('tri'), TRIS) ? $this->parametre('tri') : 'recommande';

        // Titre de la page selon le filtre choisi
        $titre = 'Të gjitha parfumet';
        if ($recherche !== '') {
            $titre = 'Kërkimi: “' . $recherche . '”';
        } elseif ($promo) {
            $titre = 'Në zbritje';
        } elseif ($marque) {
            $titre = $marque['nom'];
        } elseif ($note) {
            $titre = 'Aroma: ' . $note['nom'];
        } elseif ($categorie) {
            $titre = 'Parfume për ' . mb_strtolower($categorie['nom']);
        }

        render('catalogue', [
            'titre'           => $titre,
            'produits'        => Produit::catalogue([
                'categorie_id' => $categorie['id'] ?? null,
                'marque_id'    => $marque['id'] ?? null,
                'note_id'      => $note['id'] ?? null,
                'q'            => $recherche,
                'promo'        => $promo,
                'tri'          => $tri,
            ]),
            'categorieActive' => $categorie,
            'marqueActive'    => $marque,
            'noteActive'      => $note,
            'recherche'       => $recherche,
            'promo'           => $promo,
            'tri'             => $tri,
            'notes'           => Note::toutes(),
            'marques'         => Marque::avecProduits(),
        ]);
    }

    // Fiche produit avec ses contenances (30 ml, 50 ml, 100 ml...)
    public function detail(): void
    {
        $produit = Produit::trouver(entier_get('id'));

        if (!$produit) {
            http_response_code(404);
            render('erreur404');
            return;
        }

        render('produit', [
            'titre'     => trim(($produit['marque'] ?? '') . ' ' . $produit['nom']),
            'produit'   => $produit,
            'variantes' => Produit::variantes((int) $produit['id']),
            'images'    => Produit::images((int) $produit['id']), // photos supplémentaires
            'notes'     => Note::pourProduit((int) $produit['id']),
            // "Mund t'ju pëlqejnë edhe" : les 4 parfums qui ont le plus de notes en commun
            'similaires' => Produit::similaires($produit),
        ]);
    }

    // Lit un paramètre de l'URL en texte ('' s'il manque ou si c'est un tableau ?x[]=...)
    private function parametre(string $nom): string
    {
        $valeur = $_GET[$nom] ?? '';
        return is_string($valeur) ? trim($valeur) : '';
    }
}

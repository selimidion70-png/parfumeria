<?php

// Liste d'envies ("Lista e dëshirave"), stockée en session comme le panier :
// $_SESSION['favoris'][id_produit] = true
class FavoriController
{
    public function index(): void
    {
        $produits = Produit::catalogue(['ids' => array_keys($_SESSION['favoris'] ?? [])]);

        // Un produit retiré de la boutique entre-temps disparaît aussi de la liste
        $_SESSION['favoris'] = array_fill_keys(array_column($produits, 'id'), true);

        render('favoris', ['titre' => 'Lista e dëshirave', 'produits' => $produits]);
    }

    // Ajoute le produit s'il n'est pas dans la liste, sinon le retire (clic sur le cœur)
    public function basculer(): void
    {
        if (!est_post()) {
            redirect('favoris');
        }
        csrf_verifier();

        // champ_post() et pas (int) $_POST[...] : (int) d'un tableau donne 1, ce qui viserait le produit n°1
        $produit = Produit::trouver((int) champ_post('produit_id'));
        if ($produit) {
            if (est_favori((int) $produit['id'])) {
                unset($_SESSION['favoris'][$produit['id']]);
                flash('succes', $produit['nom'] . ' u hoq nga lista e dëshirave.');
            } else {
                $_SESSION['favoris'][$produit['id']] = true;
                flash('succes', $produit['nom'] . ' u shtua në listën e dëshirave.');
            }
        }

        $this->retourPagePrecedente();
    }

    /**
     * Revient sur la page où le client a cliqué.
     * Le formulaire envoie "retour" = la partie après le "?" (ex : page=produits&categorie=femra).
     * On la redécoupe et on repasse par redirect() : impossible d'être renvoyé vers un autre site.
     */
    private function retourPagePrecedente(): void
    {
        parse_str(champ_post('retour'), $params);
        $page = is_string($params['page'] ?? null) ? $params['page'] : 'home';
        unset($params['page']);
        redirect($page, $params);
    }
}

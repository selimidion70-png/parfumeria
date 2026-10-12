<?php

// Le panier est stocké en session : $_SESSION['panier'][id_variante] = quantité
class PanierController
{
    /**
     * Transforme le panier de la session en lignes complètes (nom, prix, stock...).
     * Renvoie [lignes, sous-total]. Utilisé aussi par CommandeController.
     */
    public static function contenu(): array
    {
        $lignes = [];
        $sousTotal = 0.0;

        foreach ($_SESSION['panier'] ?? [] as $varianteId => $quantite) {
            $variante = Produit::variante((int) $varianteId);
            if (!$variante) {
                unset($_SESSION['panier'][$varianteId]); // produit supprimé entre-temps
                continue;
            }
            $ligne = [
                'variante_id' => (int) $variante['id'],
                'produit_id'  => (int) $variante['produit_id'],
                'nom'           => $variante['nom'],
                'marque'        => $variante['marque'],
                'image'         => $variante['image'],
                'contenance_ml' => (int) $variante['contenance_ml'],
                'prix'          => (float) $variante['prix'], // chaque contenance a son propre prix
                'stock'       => (int) $variante['stock'],
                'quantite'    => (int) $quantite,
            ];
            $ligne['sous_total'] = $ligne['prix'] * $ligne['quantite'];
            $sousTotal += $ligne['sous_total'];
            $lignes[] = $ligne;
        }

        return [$lignes, $sousTotal];
    }

    /**
     * Le code promo enregistré dans la session, appliqué à ce panier.
     * Renvoie ['code' => ligne de la table ou null, 'remise' => euros, 'erreur' => message ou null].
     * Un code qui ne marche plus (expiré, minimum pas atteint...) reste en session avec son message :
     * si le client ajoute un parfum et atteint le minimum, il s'applique tout seul.
     */
    public static function codePromo(float $sousTotal): array
    {
        $saisi = $_SESSION['code_promo'] ?? null;
        if (!$saisi) {
            return ['code' => null, 'remise' => 0.0, 'erreur' => null];
        }
        $code = CodePromo::trouver($saisi);
        $erreur = CodePromo::erreur($code, $sousTotal);
        return [
            'code'   => $code ?: ['code' => $saisi],
            'remise' => $erreur ? 0.0 : CodePromo::remise($code, $sousTotal),
            'erreur' => $erreur,
        ];
    }

    public function index(): void
    {
        [$lignes, $sousTotal] = self::contenu();
        render('panier', [
            'titre'     => 'Shporta',
            'lignes'    => $lignes,
            'sousTotal' => $sousTotal,
            'promo'     => self::codePromo($sousTotal),
        ]);
    }

    // Formulaire "Kodi i zbritjes" : appliquer un code, ou le retirer (bouton "retirer")
    public function code(): void
    {
        if (est_post()) {
            csrf_verifier();

            if (isset($_POST['retirer'])) {
                unset($_SESSION['code_promo']);
            } else {
                $saisi = mb_strtoupper(champ_post('code'));
                $code = $saisi !== '' ? CodePromo::trouver($saisi) : null;
                [, $sousTotal] = self::contenu();

                if (!$code || !$code['actif']) {
                    // Un code inconnu n'est pas gardé en session
                    flash('erreur', 'Ky kod zbritjeje nuk ekziston.');
                } else {
                    $_SESSION['code_promo'] = $code['code'];
                    $erreur = CodePromo::erreur($code, $sousTotal);
                    flash($erreur ? 'erreur' : 'succes', $erreur ?? 'Kodi ' . $code['code'] . ' u aplikua: ' . CodePromo::libelle($code) . '.');
                }
            }
        }
        redirect('panier');
    }

    public function ajouter(): void
    {
        if (!est_post()) {
            redirect('home');
        }
        csrf_verifier();

        $variante = Produit::variante(entier_post('variante_id'));
        $quantite = max(1, entier_post('quantite', 1));

        if (!$variante) {
            flash('erreur', 'Zgjidhni sasinë (ml).');
            redirect('home');
        }

        $dejaDansPanier = $_SESSION['panier'][$variante['id']] ?? 0;
        $nouvelleQuantite = $dejaDansPanier + $quantite;

        if ($nouvelleQuantite > $variante['stock']) {
            flash('erreur', 'Nuk ka stok të mjaftueshëm: kanë mbetur ' . $variante['stock'] . ' copë nga ' . ml($variante['contenance_ml']) . '.');
            redirect('produit', ['id' => $variante['produit_id']]);
        }

        $_SESSION['panier'][$variante['id']] = $nouvelleQuantite;
        flash('succes', $variante['nom'] . ' (' . ml($variante['contenance_ml']) . ') u shtua në shportë.');
        redirect('panier');
    }

    public function modifier(): void
    {
        if (!est_post()) {
            redirect('panier');
        }
        csrf_verifier();

        $varianteId = entier_post('variante_id');
        $quantite = entier_post('quantite', 1);
        $variante = Produit::variante($varianteId);

        if (!$variante || !isset($_SESSION['panier'][$varianteId])) {
            redirect('panier');
        }

        if ($quantite <= 0) {
            unset($_SESSION['panier'][$varianteId]);
        } elseif ($variante['stock'] <= 0) {
            // Plus de stock du tout : on retire la ligne au lieu de la garder à 0
            unset($_SESSION['panier'][$varianteId]);
            flash('erreur', $variante['nom'] . ' nuk është më në stok dhe u hoq nga shporta.');
        } elseif ($quantite > $variante['stock']) {
            $_SESSION['panier'][$varianteId] = (int) $variante['stock'];
            flash('erreur', 'Sasia u përshtat me stokun e disponueshëm (' . $variante['stock'] . ').');
        } else {
            $_SESSION['panier'][$varianteId] = $quantite;
        }
        redirect('panier');
    }

    public function supprimer(): void
    {
        if (est_post()) {
            csrf_verifier();
            unset($_SESSION['panier'][entier_post('variante_id')]);
        }
        redirect('panier');
    }
}

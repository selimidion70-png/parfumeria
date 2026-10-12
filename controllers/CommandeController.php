<?php

class CommandeController
{
    // Formulaire de livraison (GET) et enregistrement de la commande (POST)
    public function formulaire(): void
    {
        [$lignes, $sousTotal] = PanierController::contenu();

        if (empty($lignes)) {
            flash('erreur', 'Shporta juaj është e zbrazët.');
            redirect('home');
        }

        $utilisateur = utilisateur_connecte();
        $promo = PanierController::codePromo($sousTotal);
        $erreurs = [];
        $client = [
            'prenom'    => $utilisateur['prenom'] ?? '',
            'nom'       => $utilisateur['nom'] ?? '',
            'telephone' => $utilisateur['telephone'] ?? '',
            'email'     => $utilisateur['email'] ?? '',
            'adresse'   => '',
            'ville'     => '',
            'pays'      => 'Kosovë',
        ];

        if (est_post()) {
            csrf_verifier();

            foreach ($client as $champ => $valeur) {
                $client[$champ] = champ_post($champ);
            }

            // Validation : champ obligatoire + longueur maximale de la colonne dans la table commandes
            // (un texte trop long ferait planter l'enregistrement)
            $obligatoires = [
                'prenom'  => ['Emri është i detyrueshëm.', 100],
                'nom'     => ['Mbiemri është i detyrueshëm.', 100],
                'adresse' => ['Adresa është e detyrueshme.', 255],
                'ville'   => ['Qyteti është i detyrueshëm.', 100],
            ];
            foreach ($obligatoires as $champ => [$message, $longueurMax]) {
                if ($client[$champ] === '') {
                    $erreurs[$champ] = $message;
                } elseif (mb_strlen($client[$champ]) > $longueurMax) {
                    $erreurs[$champ] = 'Teksti është shumë i gjatë (deri në ' . $longueurMax . ' karaktere).';
                }
            }
            if (!telephone_valide($client['telephone'])) {
                $erreurs['telephone'] = 'Numri i telefonit nuk është i vlefshëm (vetëm shifra, me ose pa +).';
            }
            if ($client['email'] !== '' && (!filter_var($client['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($client['email']) > 150)) {
                $erreurs['email'] = 'Adresa e emailit nuk është e vlefshme.';
            }
            if (!array_key_exists($client['pays'], PAYS_LIVRAISON)) {
                $erreurs['pays'] = 'Zgjidhni shtetin e dorëzimit.';
            }
            // Le client croit avoir une réduction : on ne commande pas en silence au prix plein
            if ($promo['erreur']) {
                $erreurs['promo'] = $promo['erreur'];
            }
            foreach ($lignes as $ligne) {
                if ($ligne['quantite'] > $ligne['stock']) {
                    $erreurs['stock'] = 'Nuk ka stok të mjaftueshëm për ' . $ligne['nom'] . ' (' . ml($ligne['contenance_ml']) . '). Ndryshoni shportën.';
                }
            }

            if (empty($erreurs)) {
                // Code promo : seulement s'il est valable maintenant (un code expiré affiché dans le panier est ignoré).
                // Commande::creer() le revérifie de toute façon dans sa transaction.
                $code = $promo['remise'] > 0 ? $promo['code']['code'] : null;
                try {
                    $commandeId = Commande::creer($client, $lignes, $sousTotal, $code, $utilisateur['id'] ?? null);
                    Notification::nouvelleCommande($commandeId); // email au cousin (un échec ne bloque pas la commande)
                    $_SESSION['panier'] = [];
                    unset($_SESSION['code_promo']);
                    $_SESSION['derniere_commande'] = $commandeId; // autorise l'affichage de la confirmation
                    redirect('confirmation', ['id' => $commandeId]);
                } catch (RuntimeException $e) {
                    $erreurs['stock'] = $e->getMessage();
                }
            }
        }

        render('commande', [
            'titre'     => 'Porosia',
            'lignes'    => $lignes,
            'sousTotal' => $sousTotal,
            'promo'     => $promo,
            'client'    => $client,
            'erreurs'   => $erreurs,
        ]);
    }

    public function confirmation(): void
    {
        $commande = Commande::trouver(entier_get('id'));

        if (!$commande || !$this->peutVoir($commande)) {
            http_response_code(404);
            render('erreur404');
            return;
        }

        render('confirmation', [
            'titre'    => 'Porosia u konfirmua',
            'commande' => $commande,
            'details'  => Commande::details((int) $commande['id']),
        ]);
    }

    // Historique des commandes du client connecté
    public function historique(): void
    {
        $utilisateur = utilisateur_connecte();
        if (!$utilisateur) {
            flash('erreur', 'Kyçuni për të parë porositë tuaja.');
            redirect('connexion');
        }

        $commandes = Commande::parUtilisateur((int) $utilisateur['id']);
        foreach ($commandes as &$commande) {
            $commande['details'] = Commande::details((int) $commande['id']);
        }
        unset($commande);

        render('mes_commandes', [
            'titre'     => 'Porositë e mia',
            'commandes' => $commandes,
        ]);
    }

    // Une commande n'est visible que par son propriétaire, ou juste après l'avoir passée
    private function peutVoir(array $commande): bool
    {
        $utilisateur = utilisateur_connecte();
        if ($utilisateur && (int) $commande['utilisateur_id'] === (int) $utilisateur['id']) {
            return true;
        }
        return (int) ($_SESSION['derniere_commande'] ?? 0) === (int) $commande['id'];
    }
}

<?php

class AuthController
{
    public function inscription(): void
    {
        if (utilisateur_connecte()) {
            redirect('mes_commandes');
        }

        $erreurs = [];
        $donnees = ['prenom' => '', 'nom' => '', 'email' => '', 'telephone' => ''];

        if (est_post()) {
            csrf_verifier();

            foreach ($donnees as $champ => $valeur) {
                $donnees[$champ] = champ_post($champ);
            }
            $donnees['email'] = mb_strtolower($donnees['email']); // même format qu'à la connexion
            // (string) : si quelqu'un envoie un tableau, on obtient un texte au lieu d'une erreur
            $motDePasse = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';
            $confirmation = is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '';

            // Les longueurs maximales sont celles des colonnes de la table utilisateurs
            if ($donnees['prenom'] === '' || mb_strlen($donnees['prenom']) > 100) {
                $erreurs['prenom'] = 'Emri është i detyrueshëm (deri në 100 karaktere).';
            }
            if ($donnees['nom'] === '' || mb_strlen($donnees['nom']) > 100) {
                $erreurs['nom'] = 'Mbiemri është i detyrueshëm (deri në 100 karaktere).';
            }
            if ($donnees['telephone'] !== '' && !telephone_valide($donnees['telephone'])) {
                $erreurs['telephone'] = 'Numri i telefonit nuk është i vlefshëm (vetëm shifra, me ose pa +).';
            }
            if (!filter_var($donnees['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($donnees['email']) > 150) {
                $erreurs['email'] = 'Adresa e emailit nuk është e vlefshme.';
            } elseif (Utilisateur::trouverParEmail($donnees['email'])) {
                $erreurs['email'] = 'Ekziston tashmë një llogari me këtë email.';
            }
            if (strlen($motDePasse) < 8) {
                $erreurs['mot_de_passe'] = 'Fjalëkalimi duhet të ketë së paku 8 karaktere.';
            } elseif ($motDePasse !== $confirmation) {
                $erreurs['confirmation'] = 'Fjalëkalimet nuk përputhen.';
            }

            if (empty($erreurs)) {
                $id = Utilisateur::creer($donnees['prenom'], $donnees['nom'], $donnees['email'], $motDePasse, $donnees['telephone']);
                $this->ouvrirSession(['id' => $id] + $donnees);
                flash('succes', 'Mirë se vini, ' . $donnees['prenom'] . '! Llogaria juaj u krijua.');
                redirect('home');
            }
        }

        render('inscription', ['titre' => 'Regjistrimi', 'donnees' => $donnees, 'erreurs' => $erreurs]);
    }

    public function connexion(): void
    {
        if (utilisateur_connecte()) {
            redirect('mes_commandes');
        }

        $erreur = null;
        $email = '';

        if (est_post()) {
            csrf_verifier();

            $email = mb_strtolower(champ_post('email'));
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            // Anti-force brute : après 5 erreurs en 15 min, on bloque avant même de vérifier le mot de passe
            if (Utilisateur::tropDEchecs($email, $ip)) {
                $erreur = 'Shumë përpjekje të gabuara. Provoni përsëri pas 15 minutash.';
            } else {
                $utilisateur = Utilisateur::trouverParEmail($email);

                if ($utilisateur && password_verify(is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '', $utilisateur['mot_de_passe'])) {
                    Utilisateur::effacerEchecs($email);
                    $this->ouvrirSession($utilisateur);
                    flash('succes', 'Mirë se u kthyet, ' . $utilisateur['prenom'] . '!');
                    redirect('home');
                }
                Utilisateur::noterEchec($email, $ip);
                $erreur = 'Email ose fjalëkalim i pasaktë.';
            }
        }

        render('connexion', ['titre' => 'Kyçja', 'email' => $email, 'erreur' => $erreur]);
    }

    public function deconnexion(): void
    {
        if (est_post()) {
            csrf_verifier();
            unset($_SESSION['utilisateur'], $_SESSION['derniere_commande']);
            session_regenerate_id(true);
            flash('succes', 'Jeni çkyçur.');
        }
        redirect('home');
    }

    // Étape 1 : le client tape son email, on lui envoie un lien
    public function motDePasseOublie(): void
    {
        if (utilisateur_connecte()) {
            redirect('mes_commandes');
        }

        $email = '';

        if (est_post()) {
            csrf_verifier();
            $email = mb_strtolower(champ_post('email'));
            $utilisateur = filter_var($email, FILTER_VALIDATE_EMAIL) ? Utilisateur::trouverParEmail($email) : null;

            if ($utilisateur) {
                $jeton = Utilisateur::creerJetonReinitialisation((int) $utilisateur['id']);
                if ($jeton !== null) {
                    Notification::reinitialisationMotDePasse($utilisateur, $jeton);
                }
            }

            // Toujours le même message, que le compte existe ou non :
            // sinon n'importe qui pourrait tester quels emails sont inscrits chez nous
            flash('succes', 'Nëse ekziston një llogari me këtë email, do të merrni një lidhje për të ndryshuar fjalëkalimin. Kontrolloni edhe dosjen Spam.');
            redirect('connexion');
        }

        render('mot_de_passe_oublie', ['titre' => 'Keni harruar fjalëkalimin?', 'email' => $email]);
    }

    // Étape 2 : le client arrive depuis le lien de l'email et choisit un nouveau mot de passe
    public function nouveauMotDePasse(): void
    {
        // Le jeton arrive dans l'URL (GET), puis dans un champ caché du formulaire (POST)
        $jeton = $_POST['jeton'] ?? $_GET['jeton'] ?? '';
        $jeton = is_string($jeton) ? $jeton : '';

        // Un vrai jeton = exactement 64 caractères hexadécimaux : inutile d'interroger la base sinon
        $utilisateur = preg_match('/^[0-9a-f]{64}$/', $jeton) ? Utilisateur::trouverParJeton($jeton) : null;
        if (!$utilisateur) {
            flash('erreur', 'Kjo lidhje nuk është e vlefshme ose ka skaduar. Kërkoni një lidhje të re.');
            redirect('mot_de_passe_oublie');
        }

        $erreurs = [];

        if (est_post()) {
            csrf_verifier();
            $motDePasse = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';
            $confirmation = is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '';

            // Mêmes règles qu'à l'inscription
            if (strlen($motDePasse) < 8) {
                $erreurs['mot_de_passe'] = 'Fjalëkalimi duhet të ketë së paku 8 karaktere.';
            } elseif ($motDePasse !== $confirmation) {
                $erreurs['confirmation'] = 'Fjalëkalimet nuk përputhen.';
            }

            if (empty($erreurs)) {
                Utilisateur::changerMotDePasse((int) $utilisateur['id'], $motDePasse);
                Utilisateur::effacerEchecs($utilisateur['email']); // débloque le compte s'il était bloqué
                flash('succes', 'Fjalëkalimi u ndryshua. Tani mund të kyçeni.');
                redirect('connexion');
            }
        }

        render('nouveau_mot_de_passe', ['titre' => 'Fjalëkalimi i ri', 'jeton' => $jeton, 'erreurs' => $erreurs]);
    }

    // On garde en session uniquement ce qui est utile (jamais le mot de passe)
    private function ouvrirSession(array $utilisateur): void
    {
        session_regenerate_id(true);
        $_SESSION['utilisateur'] = [
            'id'        => (int) $utilisateur['id'],
            'prenom'    => $utilisateur['prenom'],
            'nom'       => $utilisateur['nom'],
            'email'     => $utilisateur['email'],
            'telephone' => $utilisateur['telephone'] ?? '',
            'est_admin' => (int) ($utilisateur['est_admin'] ?? 0),
        ];
    }
}

<?php

// Espace admin : commandes, produits, variantes et stock.
// Chaque méthode commence par admin_requis() : seuls les comptes avec est_admin = 1 y ont accès.
class AdminController
{
    private const IMAGE_TAILLE_MAX = 3 * 1024 * 1024; // 3 Mo
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const PHOTOS_MAX = 8;                     // photos supplémentaires par parfum (en plus de la principale)

    // ---------- Tableau de bord ----------

    public function tableauDeBord(): void
    {
        admin_requis();

        render('admin/tableau_de_bord', [
            'titre'         => 'Paneli',
            'parStatut'     => Commande::compterParStatut(),
            'totalEncaisse' => Commande::totalEncaisse(),
            'enAttente'     => Commande::toutes('en_attente'),
            'stockFaible'   => Produit::stockFaible(),
        ]);
    }

    // ---------- Statistiques ----------

    public function statistiques(): void
    {
        admin_requis();

        render('admin/statistiques', [
            'titre'      => 'Statistikat',
            'resume'     => Commande::statsResume(),
            'parMois'    => Commande::statsParMois(),
            'plusVendus' => Commande::plusVendus(),
            'parPays'    => Commande::statsParPays(),
        ]);
    }

    // ---------- Marques et notes olfactives ----------

    /**
     * GET : affiche les deux listes.
     * POST : un champ caché "action" dit quoi faire (renommer, supprimer, ajouter),
     *        "id" désigne la marque ou la note concernée.
     */
    public function marquesNotes(): void
    {
        admin_requis();

        if (est_post()) {
            csrf_verifier();
            $action = champ_post('action');
            $id = (int) champ_post('id');
            $nom = champ_post('nom');

            switch ($action) {
                case 'marque_renommer':
                    if ($erreur = $this->verifierNom($nom, 100, Marque::nomPris($nom, $id))) {
                        flash('erreur', $erreur);
                    } else {
                        Marque::renommer($id, $nom);
                        flash('succes', 'Marka u riemërtua: ' . $nom . '.');
                    }
                    break;

                case 'marque_supprimer':
                    if (Marque::supprimer($id)) {
                        flash('succes', 'Marka u fshi.');
                    } else {
                        flash('erreur', 'Kjo markë përdoret ende nga parfume. Ndryshoni markën e tyre fillimisht.');
                    }
                    break;

                case 'note_ajouter':
                    if ($erreur = $this->verifierNom($nom, 60, Note::nomPris($nom))) {
                        flash('erreur', $erreur);
                    } else {
                        Note::creer($nom);
                        flash('succes', 'Aroma “' . $nom . '” u shtua.');
                    }
                    break;

                case 'note_renommer':
                    if ($erreur = $this->verifierNom($nom, 60, Note::nomPris($nom, $id))) {
                        flash('erreur', $erreur);
                    } else {
                        Note::renommer($id, $nom);
                        flash('succes', 'Aroma u riemërtua: ' . $nom . '.');
                    }
                    break;

                case 'note_supprimer':
                    Note::supprimer($id);
                    flash('succes', 'Aroma u fshi.');
                    break;
            }
            redirect('admin_marques_notes');
        }

        render('admin/marques_notes', [
            'titre'   => 'Markat & notat',
            'marques' => Marque::toutesAvecNombre(),
            'notes'   => Note::toutesAvecNombre(),
        ]);
    }

    // ---------- Codes promo ----------

    // GET : liste + formulaire de création ; POST : action = creer, basculer ou supprimer
    public function codes(): void
    {
        admin_requis();

        $erreurs = [];
        $donnees = ['code' => '', 'type' => 'pourcentage', 'valeur' => '', 'minimum' => '', 'expire_le' => '', 'utilisations_max' => ''];

        if (est_post()) {
            csrf_verifier();
            $action = champ_post('action');
            $id = (int) champ_post('id');

            if ($action === 'basculer') {
                CodePromo::basculer($id);
                redirect('admin_codes');
            }
            if ($action === 'supprimer') {
                CodePromo::supprimer($id);
                flash('succes', 'Kodi u fshi.');
                redirect('admin_codes');
            }

            // action = creer
            foreach ($donnees as $champ => $valeur) {
                $donnees[$champ] = champ_post($champ);
            }
            $donnees['code'] = mb_strtoupper($donnees['code']);

            // Lettres, chiffres, - et _ seulement : facile à dicter au téléphone ou à écrire sur Instagram
            if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $donnees['code'])) {
                $erreurs['code'] = 'Kodi: 3 deri në 30 shkronja ose shifra, pa hapësira (p.sh. VERA10).';
            } elseif (CodePromo::trouver($donnees['code'])) {
                $erreurs['code'] = 'Ky kod ekziston tashmë.';
            }
            if (!in_array($donnees['type'], ['pourcentage', 'montant'], true)) {
                $donnees['type'] = 'pourcentage';
            }
            $valeur = $this->lirePrix($donnees['valeur']);
            if ($valeur === null || ($donnees['type'] === 'pourcentage' && $valeur > 90)) {
                $erreurs['valeur'] = $donnees['type'] === 'pourcentage'
                    ? 'Përqindja duhet të jetë nga 1 deri në 90.'
                    : 'Shkruani një shumë të vlefshme (p.sh. 5).';
            }
            // Champs facultatifs : vides = pas de condition
            $minimum = $donnees['minimum'] === '' ? null : $this->lirePrix($donnees['minimum']);
            if ($donnees['minimum'] !== '' && $minimum === null) {
                $erreurs['minimum'] = 'Shuma minimale nuk është e vlefshme.';
            }
            $date = DateTime::createFromFormat('!Y-m-d', $donnees['expire_le']);
            if ($donnees['expire_le'] !== '' && (!$date || $date->format('Y-m-d') !== $donnees['expire_le'])) {
                $erreurs['expire_le'] = 'Data nuk është e vlefshme.';
            }
            $max = $donnees['utilisations_max'] === '' ? null : filter_var($donnees['utilisations_max'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($max === false) {
                $erreurs['utilisations_max'] = 'Shkruani një numër 1 ose më shumë (ose lëreni bosh).';
            }

            if (empty($erreurs)) {
                CodePromo::creer([
                    'code'             => $donnees['code'],
                    'type'             => $donnees['type'],
                    'valeur'           => $valeur,
                    'minimum'          => $minimum,
                    'expire_le'        => $donnees['expire_le'] ?: null,
                    'utilisations_max' => $max,
                ]);
                flash('succes', 'Kodi ' . $donnees['code'] . ' u krijua.');
                redirect('admin_codes');
            }
        }

        render('admin/codes', [
            'titre'   => 'Kodet e zbritjes',
            'codes'   => CodePromo::tous(),
            'donnees' => $donnees,
            'erreurs' => $erreurs,
        ]);
    }

    // Message d'erreur pour un nom de marque ou de note, ou null s'il est correct
    private function verifierNom(string $nom, int $longueurMax, bool $dejaPris): ?string
    {
        if ($nom === '' || mb_strlen($nom) > $longueurMax) {
            return 'Emri është i detyrueshëm (deri në ' . $longueurMax . ' karaktere).';
        }
        if ($dejaPris) {
            return 'Ekziston tashmë një me emrin “' . $nom . '”.';
        }
        return null;
    }

    // ---------- Commandes ----------

    public function commandes(): void
    {
        admin_requis();

        // Filtre facultatif : ?statut=en_attente
        $statut = $_GET['statut'] ?? null;
        if (!is_string($statut) || !array_key_exists($statut, STATUTS_COMMANDE)) {
            $statut = null;
        }

        render('admin/commandes', [
            'titre'      => 'Porositë',
            'commandes'  => Commande::toutes($statut),
            'parStatut'  => Commande::compterParStatut(),
            'statutActif' => $statut,
        ]);
    }

    // Détail d'une commande (GET) et changement de statut (POST)
    public function commande(): void
    {
        admin_requis();

        $commande = Commande::trouver(entier_get('id'));
        if (!$commande) {
            http_response_code(404);
            render('erreur404');
            return;
        }

        if (est_post()) {
            csrf_verifier();
            $statut = $_POST['statut'] ?? '';

            if (!is_string($statut) || !array_key_exists($statut, STATUTS_COMMANDE)) {
                flash('erreur', 'Statusi nuk është i vlefshëm.');
            } elseif ($statut !== $commande['statut']) {
                try {
                    Commande::changerStatut((int) $commande['id'], $statut);
                    flash('succes', 'Statusi u ndryshua në « ' . STATUTS_COMMANDE[$statut] . ' ».'
                        . ($statut === 'annulee' ? ' Artikujt u kthyen në stok.' : ''));
                } catch (RuntimeException $e) {
                    flash('erreur', $e->getMessage());
                }
            }
            redirect('admin_commande', ['id' => $commande['id']]);
        }

        render('admin/commande', [
            'titre'    => 'Porosia nr. ' . $commande['id'],
            'commande' => $commande,
            'details'  => Commande::details((int) $commande['id']),
        ]);
    }

    // Bon de livraison à imprimer (à mettre dans le colis ou à donner au livreur)
    public function commandeImprimer(): void
    {
        admin_requis();

        $commande = Commande::trouver(entier_get('id'));
        if (!$commande) {
            http_response_code(404);
            render('erreur404');
            return;
        }

        $details = Commande::details((int) $commande['id']);
        $sousTotal = array_sum(array_map(fn($d) => $d['prix_unitaire'] * $d['quantite'], $details));

        // Pas de render() : cette page n'a ni menu ni pied de page, seulement le bon à imprimer
        require __DIR__ . '/../views/admin/commande_imprimer.php';
    }

    // ---------- Produits ----------

    public function produits(): void
    {
        admin_requis();

        render('admin/produits', [
            'titre'    => 'Produktet',
            'produits' => Produit::tousAdmin(),
        ]);
    }

    // Formulaire produit : création (sans id) ou modification (avec id)
    public function produit(): void
    {
        admin_requis();

        $id = entier_get('id');
        $produit = $id ? Produit::trouverAdmin($id) : null;
        if ($id && !$produit) {
            http_response_code(404);
            render('erreur404');
            return;
        }

        $erreurs = [];
        $donnees = [
            'categorie_id' => $produit['categorie_id'] ?? '',
            'marque'       => $produit['marque'] ?? '',      // nom de la marque (texte libre)
            'nom'          => $produit['nom'] ?? '',
            'description'  => $produit['description'] ?? '',
            'actif'        => $produit['actif'] ?? 1,
            'en_vedette'   => $produit['en_vedette'] ?? 0,
            'notes'        => $produit ? array_column(Note::pourProduit($id), 'id') : [],
        ];

        if (est_post()) {
            csrf_verifier();

            $donnees['categorie_id'] = entier_post('categorie_id');
            $donnees['marque']       = champ_post('marque');
            $donnees['nom']          = champ_post('nom');
            $donnees['description']  = champ_post('description');
            $donnees['actif']        = isset($_POST['actif']) ? 1 : 0;
            $donnees['en_vedette']   = isset($_POST['en_vedette']) ? 1 : 0;
            $donnees['notes']        = is_array($_POST['notes'] ?? null) ? array_map('intval', $_POST['notes']) : [];

            if (!Categorie::existe($donnees['categorie_id'])) {
                $erreurs['categorie_id'] = 'Zgjidhni kategorinë.';
            }
            if ($donnees['nom'] === '' || mb_strlen($donnees['nom']) > 150) {
                $erreurs['nom'] = 'Emri është i detyrueshëm (deri në 150 karaktere).';
            }
            if (mb_strlen($donnees['marque']) > 100) {
                $erreurs['marque'] = 'Emri i markës është shumë i gjatë.';
            }

            // La photo est facultative ; on la vérifie avant d'enregistrer quoi que ce soit
            $photo = $_FILES['image'] ?? null;
            $avecPhoto = is_array($photo) && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($avecPhoto) {
                $erreurPhoto = $this->verifierImage($photo);
                if ($erreurPhoto) {
                    $erreurs['image'] = $erreurPhoto;
                }
            }

            if (empty($erreurs)) {
                // Marque vide = pas de marque ; sinon on la retrouve (ou on la crée)
                $donnees['marque_id'] = $donnees['marque'] !== '' ? Marque::trouverOuCreer($donnees['marque']) : null;

                if ($produit) {
                    Produit::modifier($id, $donnees);
                } else {
                    $id = Produit::creer($donnees);
                }
                Note::definirPourProduit($id, $donnees['notes']);

                if ($avecPhoto) {
                    $this->enregistrerImage($id, $photo, $produit['image'] ?? null);
                } elseif ($produit && isset($_POST['supprimer_image'])) {
                    $this->supprimerFichierImage($produit['image']);
                    Produit::changerImage($id, null);
                }

                flash('succes', $produit ? 'Produkti u ruajt.' : 'Produkti u krijua. Tani shtoni sasitë (ml), çmimet dhe stokun.');
                redirect('admin_produit', ['id' => $id]);
            }
        }

        render('admin/produit', [
            'titre'      => $produit ? $produit['nom'] : 'Produkt i ri',
            'produit'    => $produit,
            'donnees'    => $donnees,
            'erreurs'    => $erreurs,
            'categories' => Categorie::toutes(),
            'marques'    => Marque::toutes(),
            'notes'      => Note::toutes(),
            'variantes'  => $produit ? Produit::variantes($id) : [],
            'images'     => $produit ? Produit::images($id) : [],
            'photosMax'  => self::PHOTOS_MAX,
        ]);
    }

    // ---------- Variantes = contenances (ml + prix + ancien prix + stock) ----------

    public function varianteAjouter(): void
    {
        admin_requis();
        $produit = $this->produitDuFormulaire();
        csrf_verifier();

        $ml         = entier_post('contenance_ml');
        $prix       = $this->lirePrix($_POST['prix'] ?? '');
        $prixAncien = $this->lirePrix($_POST['prix_ancien'] ?? '');
        $stock      = entier_post('stock');

        if ($ml < 1 || $ml > 1000 || $prix === null || $stock < 0) {
            flash('erreur', 'Shkruani sasinë (ml), një çmim të vlefshëm (p.sh. 49.90) dhe një stok 0 ose më shumë.');
        } else {
            try {
                Produit::ajouterVariante((int) $produit['id'], $ml, $prix, $this->promoValide($prix, $prixAncien), $stock);
                flash('succes', 'U shtua: ' . ml($ml) . ' — ' . prix($prix) . '.');
            } catch (PDOException $e) {
                // 23000 = la clé unique (produit, contenance) existe déjà
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                flash('erreur', ml($ml) . ' ekziston tashmë për këtë parfum. Ndryshoni çmimin ose stokun në tabelë.');
            }
        }
        redirect('admin_produit', ['id' => $produit['id']]);
    }

    // Enregistre tout le tableau des contenances en une fois :
    // prix[id_variante], prix_ancien[id_variante], stock[id_variante]
    public function variantes(): void
    {
        admin_requis();
        $produit = $this->produitDuFormulaire();
        csrf_verifier();

        $prixListe    = is_array($_POST['prix'] ?? null) ? $_POST['prix'] : [];
        $anciensListe = is_array($_POST['prix_ancien'] ?? null) ? $_POST['prix_ancien'] : [];
        $stocksListe  = is_array($_POST['stock'] ?? null) ? $_POST['stock'] : [];

        $ignorees = 0;
        foreach ($prixListe as $varianteId => $valeur) {
            $prix = $this->lirePrix($valeur);
            if ($prix === null) {
                $ignorees++; // prix invalide : on ne touche pas à cette ligne
                continue;
            }
            $prixAncien = $this->promoValide($prix, $this->lirePrix($anciensListe[$varianteId] ?? ''));
            $valeurStock = $stocksListe[$varianteId] ?? 0;
            $stock = is_numeric($valeurStock) ? max(0, (int) $valeurStock) : 0; // is_numeric : refuse un tableau
            Produit::modifierVariante((int) $produit['id'], (int) $varianteId, $prix, $prixAncien, $stock);
        }

        if ($ignorees) {
            flash('erreur', 'U ruajt, por ' . $ignorees . ' rresht me çmim të pavlefshëm nuk u ndryshua.');
        } else {
            flash('succes', 'Çmimet dhe stoku u ruajtën.');
        }
        redirect('admin_produit', ['id' => $produit['id']]);
    }

    public function varianteSupprimer(): void
    {
        admin_requis();
        $produit = $this->produitDuFormulaire();
        csrf_verifier();

        Produit::supprimerVariante((int) $produit['id'], entier_post('variante_id'));
        flash('succes', 'Sasia u fshi.');
        redirect('admin_produit', ['id' => $produit['id']]);
    }

    // ---------- Photos supplémentaires (galerie) ----------

    // Ajout de plusieurs photos d'un coup (<input type="file" name="photos[]" multiple>)
    public function photosAjouter(): void
    {
        admin_requis();

        // Si l'envoi dépasse post_max_size, PHP vide $_POST et $_FILES :
        // sans ce test, on afficherait l'erreur CSRF « formulaire expiré », incompréhensible ici
        if (est_post() && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            flash('erreur', 'Fotot janë shumë të mëdha për t\'u dërguar së bashku. Dërgoni më pak foto njëherësh.');
            redirect('admin_produits');
        }

        $produit = $this->produitDuFormulaire();
        csrf_verifier();

        // $_FILES['photos'] arrive "à l'envers" : name[0], name[1]... tmp_name[0], tmp_name[1]...
        // On le remet sous forme d'une liste de photos : [ [name, tmp_name, error, size], ... ]
        $envoi = $_FILES['photos'] ?? null;
        $photos = [];
        if (is_array($envoi) && is_array($envoi['tmp_name'] ?? null)) {
            foreach ($envoi['tmp_name'] as $i => $tmp) {
                if (($envoi['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue; // champ laissé vide
                }
                $photos[] = ['tmp_name' => $tmp, 'error' => $envoi['error'][$i], 'size' => $envoi['size'][$i]];
            }
        }

        $placesLibres = self::PHOTOS_MAX - count(Produit::images((int) $produit['id']));
        $ajoutees = 0;
        $refusees = 0;
        foreach ($photos as $photo) {
            if ($ajoutees >= $placesLibres || $this->verifierImage($photo) !== null) {
                $refusees++;
                continue;
            }
            $fichier = $this->deplacerImage((int) $produit['id'], $photo);
            if ($fichier) {
                Produit::ajouterImage((int) $produit['id'], $fichier);
                $ajoutees++;
            }
        }

        if (!$photos) {
            flash('erreur', 'Zgjidhni të paktën një foto.');
        } elseif ($refusees) {
            flash('erreur', $ajoutees . ' foto u shtuan, ' . $refusees . ' u refuzuan (vetëm JPG, PNG ose WEBP deri në 3 MB, maksimumi ' . self::PHOTOS_MAX . ' foto shtesë).');
        } else {
            flash('succes', $ajoutees . ' foto u shtuan.');
        }
        redirect('admin_produit', ['id' => $produit['id']]);
    }

    public function photoPrincipale(): void
    {
        admin_requis();
        $produit = $this->produitDuFormulaire();
        csrf_verifier();

        $image = Produit::image((int) $produit['id'], (int) champ_post('image_id'));
        if ($image) {
            Produit::definirImagePrincipale($produit, $image);
            flash('succes', 'Fotoja kryesore u ndryshua.');
        }
        redirect('admin_produit', ['id' => $produit['id']]);
    }

    public function photoSupprimer(): void
    {
        admin_requis();
        $produit = $this->produitDuFormulaire();
        csrf_verifier();

        $image = Produit::image((int) $produit['id'], (int) champ_post('image_id'));
        if ($image) {
            Produit::supprimerImage((int) $image['id']);
            $this->supprimerFichierImage($image['fichier']);
            flash('succes', 'Fotoja u fshi.');
        }
        redirect('admin_produit', ['id' => $produit['id']]);
    }

    // ---------- Outils internes ----------

    // "49,90" ou "49.90" -> 49.9 ; vide, invalide ou hors limites -> null
    private function lirePrix(mixed $valeur): ?float
    {
        if (!is_string($valeur)) {
            return null;
        }
        $valeur = str_replace(',', '.', trim($valeur));
        if (!is_numeric($valeur) || $valeur <= 0 || $valeur > 99999) {
            return null;
        }
        return round((float) $valeur, 2);
    }

    // L'ancien prix n'a de sens que s'il est plus haut que le prix actuel
    private function promoValide(float $prix, ?float $prixAncien): ?float
    {
        return ($prixAncien !== null && $prixAncien > $prix) ? $prixAncien : null;
    }

    // Les formulaires de variantes envoient produit_id en POST ; on vérifie qu'il existe
    private function produitDuFormulaire(): array
    {
        if (!est_post()) {
            redirect('admin_produits');
        }
        $produit = Produit::trouverAdmin(entier_post('produit_id'));
        if (!$produit) {
            flash('erreur', 'Produkti nuk ekziston.');
            redirect('admin_produits');
        }
        return $produit;
    }

    // Renvoie un message d'erreur, ou null si l'image est correcte
    private function verifierImage(array $photo): ?string
    {
        if ($photo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($photo['tmp_name'])) {
            return 'Fotoja nuk u ngarkua. Provoni përsëri.';
        }
        if ($photo['size'] > self::IMAGE_TAILLE_MAX) {
            return 'Fotoja është shumë e madhe (maksimumi 3 MB).';
        }
        // On regarde le vrai contenu du fichier, pas son nom (un .jpg peut cacher autre chose)
        $type = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
        if (!isset(self::IMAGE_TYPES[$type]) || getimagesize($photo['tmp_name']) === false) {
            return 'Lejohen vetëm foto JPG, PNG ose WEBP.';
        }
        return null;
    }

    // Photo principale : on enregistre la nouvelle, puis on supprime l'ancienne
    private function enregistrerImage(int $produitId, array $photo, ?string $ancienne): void
    {
        $fichier = $this->deplacerImage($produitId, $photo);
        if ($fichier) {
            Produit::changerImage($produitId, $fichier);
            $this->supprimerFichierImage($ancienne);
        }
    }

    // Range la photo (déjà vérifiée) dans public/img/produits/ et renvoie son nom, ou null en cas d'échec.
    // Nom de fichier choisi par nous, jamais celui envoyé.
    private function deplacerImage(int $produitId, array $photo): ?string
    {
        $type = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
        $fichier = 'produit-' . $produitId . '-' . bin2hex(random_bytes(4)) . '.' . self::IMAGE_TYPES[$type];

        return move_uploaded_file($photo['tmp_name'], $this->dossierImages() . $fichier) ? $fichier : null;
    }

    private function supprimerFichierImage(?string $fichier): void
    {
        // basename() : on ne supprime jamais en dehors du dossier des photos
        if ($fichier && is_file($this->dossierImages() . basename($fichier))) {
            unlink($this->dossierImages() . basename($fichier));
        }
    }

    private function dossierImages(): string
    {
        return __DIR__ . '/../public/img/produits/';
    }
}

<?php

class Produit
{
    // Colonnes communes à toutes les listes de produits (cartes produit).
    // Le prix affiché est celui de la plus petite contenance ("nga 29,90 €").
    // reduction_max = plus grosse promo parmi les contenances (elle peut être sur le 100 ml seulement).
    private const SELECT_CARTE =
        'SELECT p.*, c.nom AS categorie, c.slug AS categorie_slug, m.nom AS marque, m.slug AS marque_slug,
                (SELECT MIN(v.prix) FROM variantes v WHERE v.produit_id = p.id) AS prix_min,
                (SELECT v.prix_ancien FROM variantes v WHERE v.produit_id = p.id ORDER BY v.prix, v.id LIMIT 1) AS prix_ancien_min,
                (SELECT COALESCE(SUM(v.stock), 0) FROM variantes v WHERE v.produit_id = p.id) AS stock_total,
                (SELECT COUNT(*) FROM variantes v WHERE v.produit_id = p.id) AS nb_variantes,
                (SELECT MIN(v.id) FROM variantes v WHERE v.produit_id = p.id) AS premiere_variante,
                (SELECT MAX(ROUND((1 - v.prix / v.prix_ancien) * 100)) FROM variantes v
                  WHERE v.produit_id = p.id AND v.prix_ancien > v.prix) AS reduction_max
         FROM produits p
         JOIN categories c ON c.id = p.categorie_id
         LEFT JOIN marques m ON m.id = p.marque_id';

    /**
     * Le catalogue, avec filtres facultatifs :
     *   categorie_id, marque_id, note_id, q (recherche), promo (true), vedette (true),
     *   tri (une clé de TRIS), limite (nombre max)
     * Seuls les produits actifs qui ont au moins une contenance sont affichés.
     */
    public static function catalogue(array $filtres = []): array
    {
        $sql = self::SELECT_CARTE . ' WHERE p.actif = 1 AND EXISTS (SELECT 1 FROM variantes v WHERE v.produit_id = p.id)';
        $params = [];

        if (!empty($filtres['categorie_id'])) {
            $sql .= ' AND p.categorie_id = ?';
            $params[] = $filtres['categorie_id'];
        }
        if (!empty($filtres['marque_id'])) {
            $sql .= ' AND p.marque_id = ?';
            $params[] = $filtres['marque_id'];
        }
        if (!empty($filtres['note_id'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM produit_notes pn WHERE pn.produit_id = p.id AND pn.note_id = ?)';
            $params[] = $filtres['note_id'];
        }
        if (!empty($filtres['promo'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM variantes v WHERE v.produit_id = p.id AND v.prix_ancien > v.prix)';
        }
        if (isset($filtres['ids'])) {
            // Seulement ces produits (liste d'envies). Liste vide = aucun résultat.
            if (empty($filtres['ids'])) {
                return [];
            }
            // Un "?" par id : IN (?, ?, ?)
            $sql .= ' AND p.id IN (' . implode(', ', array_fill(0, count($filtres['ids']), '?')) . ')';
            array_push($params, ...array_map('intval', $filtres['ids']));
        }
        if (!empty($filtres['vedette'])) {
            $sql .= ' AND p.en_vedette = 1';
        }
        if (!empty($filtres['q'])) {
            // addcslashes : un "%" ou "_" tapé par le client est cherché tel quel, pas comme joker
            $mot = '%' . addcslashes($filtres['q'], '%_\\') . '%';
            $sql .= ' AND (p.nom LIKE ? OR p.description LIKE ? OR m.nom LIKE ?)';
            array_push($params, $mot, $mot, $mot);
        }

        // Le tri vient d'une liste fixe : jamais de texte du client directement dans le SQL
        $sql .= match ($filtres['tri'] ?? 'recommande') {
            'nouveau'   => ' ORDER BY p.created_at DESC, p.id DESC',
            'prix_asc'  => ' ORDER BY prix_min ASC, p.id',
            'prix_desc' => ' ORDER BY prix_min DESC, p.id',
            default     => ' ORDER BY (stock_total > 0) DESC, p.en_vedette DESC, p.created_at DESC',
        };
        if (!empty($filtres['limite'])) {
            $sql .= ' LIMIT ' . (int) $filtres['limite'];
        }

        $requete = Database::getConnexion()->prepare($sql);
        $requete->execute($params);
        return $requete->fetchAll();
    }

    public static function trouver(int $id): ?array
    {
        $requete = Database::getConnexion()->prepare(self::SELECT_CARTE . ' WHERE p.id = ? AND p.actif = 1');
        $requete->execute([$id]);
        return $requete->fetch() ?: null;
    }

    // Les contenances d'un produit, de la plus petite à la plus grande
    /**
     * "Mund t'ju pëlqejnë edhe" : les parfums qui ressemblent le plus à $produit.
     * Score de ressemblance :
     *   +2 par note olfactive en commun (vanille, oud... : le critère principal)
     *   +1 si même catégorie, +1 si même marque
     * Seulement des parfums portables par le même public : pour un parfum Meshkuj,
     * on propose Meshkuj ou Unisex (jamais Femra) ; pour un Unisex, tout convient.
     */
    public static function similaires(array $produit, int $limite = 4): array
    {
        $sql = self::SELECT_CARTE . '
            WHERE p.actif = 1
              AND EXISTS (SELECT 1 FROM variantes v WHERE v.produit_id = p.id)
              AND p.id <> ?
              AND (p.categorie_id = ? OR c.slug = \'unisex\' OR ? = \'unisex\')
            ORDER BY
                (stock_total > 0) DESC,
                (
                    (SELECT COUNT(*) FROM produit_notes a
                       JOIN produit_notes b ON b.note_id = a.note_id
                      WHERE a.produit_id = p.id AND b.produit_id = ?) * 2
                    + (p.categorie_id = ?)
                    + COALESCE(p.marque_id = ?, 0)
                ) DESC,
                p.en_vedette DESC,
                p.created_at DESC
            LIMIT ' . (int) $limite;
        // COALESCE : si un des deux parfums n'a pas de marque, "=" donne NULL, ce qui annulerait tout le score

        $requete = Database::getConnexion()->prepare($sql);
        $requete->execute([
            $produit['id'],
            $produit['categorie_id'],
            $produit['categorie_slug'],
            $produit['id'],
            $produit['categorie_id'],
            $produit['marque_id'],
        ]);
        return $requete->fetchAll();
    }

    public static function variantes(int $produitId): array
    {
        $requete = Database::getConnexion()->prepare('SELECT * FROM variantes WHERE produit_id = ? ORDER BY contenance_ml');
        $requete->execute([$produitId]);
        return $requete->fetchAll();
    }

    // Une contenance précise, avec le nom et la marque du produit (pour le panier)
    public static function variante(int $varianteId): ?array
    {
        $requete = Database::getConnexion()->prepare(
            'SELECT v.*, p.nom, p.image, m.nom AS marque
             FROM variantes v
             JOIN produits p ON p.id = v.produit_id
             LEFT JOIN marques m ON m.id = p.marque_id
             WHERE v.id = ? AND p.actif = 1'
        );
        $requete->execute([$varianteId]);
        return $requete->fetch() ?: null;
    }

    // ---------- Admin ----------

    // Tous les produits, y compris les désactivés
    public static function tousAdmin(): array
    {
        return Database::getConnexion()->query(self::SELECT_CARTE . ' ORDER BY p.actif DESC, p.id DESC')->fetchAll();
    }

    // Un produit même désactivé (trouver() ne renvoie que les actifs)
    public static function trouverAdmin(int $id): ?array
    {
        $requete = Database::getConnexion()->prepare(self::SELECT_CARTE . ' WHERE p.id = ?');
        $requete->execute([$id]);
        return $requete->fetch() ?: null;
    }

    // $donnees : categorie_id, marque_id, nom, description, actif, en_vedette
    public static function creer(array $donnees): int
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare(
            'INSERT INTO produits (categorie_id, marque_id, nom, description, actif, en_vedette) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $requete->execute([$donnees['categorie_id'], $donnees['marque_id'], $donnees['nom'], $donnees['description'], $donnees['actif'], $donnees['en_vedette']]);
        return (int) $pdo->lastInsertId();
    }

    public static function modifier(int $id, array $donnees): void
    {
        $requete = Database::getConnexion()->prepare(
            'UPDATE produits SET categorie_id = ?, marque_id = ?, nom = ?, description = ?, actif = ?, en_vedette = ? WHERE id = ?'
        );
        $requete->execute([$donnees['categorie_id'], $donnees['marque_id'], $donnees['nom'], $donnees['description'], $donnees['actif'], $donnees['en_vedette'], $id]);
    }

    public static function changerImage(int $id, ?string $fichier): void
    {
        $requete = Database::getConnexion()->prepare('UPDATE produits SET image = ? WHERE id = ?');
        $requete->execute([$fichier, $id]);
    }

    // Ajoute une contenance. Lance une PDOException (code 23000) si elle existe déjà.
    // ---------- Photos supplémentaires (table produit_images) ----------

    public static function images(int $produitId): array
    {
        $requete = Database::getConnexion()->prepare('SELECT * FROM produit_images WHERE produit_id = ? ORDER BY id');
        $requete->execute([$produitId]);
        return $requete->fetchAll();
    }

    // On vérifie aussi le produit : impossible de toucher à la photo d'un autre parfum
    public static function image(int $produitId, int $imageId): ?array
    {
        $requete = Database::getConnexion()->prepare('SELECT * FROM produit_images WHERE id = ? AND produit_id = ?');
        $requete->execute([$imageId, $produitId]);
        return $requete->fetch() ?: null;
    }

    public static function ajouterImage(int $produitId, string $fichier): void
    {
        $requete = Database::getConnexion()->prepare('INSERT INTO produit_images (produit_id, fichier) VALUES (?, ?)');
        $requete->execute([$produitId, $fichier]);
    }

    public static function supprimerImage(int $imageId): void
    {
        $requete = Database::getConnexion()->prepare('DELETE FROM produit_images WHERE id = ?');
        $requete->execute([$imageId]);
    }

    /**
     * La photo supplémentaire devient la photo principale, et l'ancienne principale
     * prend sa place dans la galerie (s'il n'y en avait pas, la ligne est juste supprimée).
     * Transaction : les deux changements réussissent ensemble, ou aucun.
     */
    public static function definirImagePrincipale(array $produit, array $image): void
    {
        $pdo = Database::getConnexion();
        $pdo->beginTransaction();
        try {
            self::changerImage((int) $produit['id'], $image['fichier']);
            if ($produit['image']) {
                $requete = $pdo->prepare('UPDATE produit_images SET fichier = ? WHERE id = ?');
                $requete->execute([$produit['image'], $image['id']]);
            } else {
                self::supprimerImage((int) $image['id']);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function ajouterVariante(int $produitId, int $ml, float $prix, ?float $prixAncien, int $stock): void
    {
        $requete = Database::getConnexion()->prepare(
            'INSERT INTO variantes (produit_id, contenance_ml, prix, prix_ancien, stock) VALUES (?, ?, ?, ?, ?)'
        );
        $requete->execute([$produitId, $ml, $prix, $prixAncien, $stock]);
    }

    // Le "AND produit_id = ?" empêche de modifier la contenance d'un autre produit
    public static function modifierVariante(int $produitId, int $varianteId, float $prix, ?float $prixAncien, int $stock): void
    {
        $requete = Database::getConnexion()->prepare(
            'UPDATE variantes SET prix = ?, prix_ancien = ?, stock = ? WHERE id = ? AND produit_id = ?'
        );
        $requete->execute([$prix, $prixAncien, $stock, $varianteId, $produitId]);
    }

    // Les anciennes commandes gardent leurs infos (nom, marque, contenance sont copiés dans commande_details)
    public static function supprimerVariante(int $produitId, int $varianteId): void
    {
        $requete = Database::getConnexion()->prepare('DELETE FROM variantes WHERE id = ? AND produit_id = ?');
        $requete->execute([$varianteId, $produitId]);
    }

    // Contenances presque épuisées (pour le tableau de bord)
    public static function stockFaible(int $seuil = 3): array
    {
        $requete = Database::getConnexion()->prepare(
            'SELECT v.*, p.nom, m.nom AS marque
             FROM variantes v
             JOIN produits p ON p.id = v.produit_id
             LEFT JOIN marques m ON m.id = p.marque_id
             WHERE p.actif = 1 AND v.stock <= ?
             ORDER BY v.stock, p.nom'
        );
        $requete->execute([$seuil]);
        return $requete->fetchAll();
    }
}

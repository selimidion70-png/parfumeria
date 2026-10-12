<?php

class Marque
{
    // Marques qui ont au moins un produit visible (pour l'accueil et les filtres)
    public static function avecProduits(): array
    {
        $pdo = Database::getConnexion();
        return $pdo->query(
            'SELECT m.*, COUNT(p.id) AS nb_produits
             FROM marques m
             JOIN produits p ON p.marque_id = m.id AND p.actif = 1
             GROUP BY m.id
             ORDER BY m.nom'
        )->fetchAll();
    }

    public static function toutes(): array
    {
        $pdo = Database::getConnexion();
        return $pdo->query('SELECT * FROM marques ORDER BY nom')->fetchAll();
    }

    public static function trouverParSlug(string $slug): ?array
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('SELECT * FROM marques WHERE slug = ?');
        $requete->execute([$slug]);
        return $requete->fetch() ?: null;
    }

    // Utilisé par l'admin : on tape le nom de la marque, elle est créée si elle n'existe pas encore
    public static function trouverOuCreer(string $nom): int
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('SELECT id FROM marques WHERE nom = ?');
        $requete->execute([$nom]);
        $id = $requete->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        // Si deux marques donnent le même slug ("Dior" / "DIOR!"), slug_libre() ajoute un numéro
        $requete = $pdo->prepare('INSERT INTO marques (nom, slug) VALUES (?, ?)');
        $requete->execute([$nom, slug_libre('marques', $nom)]);
        return (int) $pdo->lastInsertId();
    }

    // ---------- Gestion dans l'admin ("Markat & notat") ----------

    // Toutes les marques, avec le nombre de parfums (même cachés) qui les utilisent
    public static function toutesAvecNombre(): array
    {
        $pdo = Database::getConnexion();
        return $pdo->query(
            'SELECT m.*, COUNT(p.id) AS nb_produits
             FROM marques m
             LEFT JOIN produits p ON p.marque_id = m.id
             GROUP BY m.id
             ORDER BY m.nom'
        )->fetchAll();
    }

    // Une autre marque porte-t-elle déjà ce nom ? (la comparaison MySQL ignore majuscules et accents)
    public static function nomPris(string $nom, int $saufId = 0): bool
    {
        $requete = Database::getConnexion()->prepare('SELECT COUNT(*) FROM marques WHERE nom = ? AND id <> ?');
        $requete->execute([$nom, $saufId]);
        return $requete->fetchColumn() > 0;
    }

    // Le slug suit le nouveau nom (les liens ?marque=... changent aussi)
    public static function renommer(int $id, string $nom): void
    {
        $requete = Database::getConnexion()->prepare('UPDATE marques SET nom = ?, slug = ? WHERE id = ?');
        $requete->execute([$nom, slug_libre('marques', $nom, $id), $id]);
    }

    /**
     * Supprime la marque seulement si aucun parfum ne l'utilise
     * (sinon ces parfums se retrouveraient sans marque sans que le cousin s'en rende compte).
     * Renvoie false si elle est encore utilisée.
     */
    public static function supprimer(int $id): bool
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('DELETE FROM marques WHERE id = ? AND NOT EXISTS (SELECT 1 FROM produits WHERE marque_id = ?)');
        $requete->execute([$id, $id]);
        return $requete->rowCount() > 0;
    }
}

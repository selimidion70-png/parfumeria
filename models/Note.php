<?php

// Notes olfactives (vanille, agrumes, oud...)
class Note
{
    public static function toutes(): array
    {
        $pdo = Database::getConnexion();
        return $pdo->query('SELECT * FROM notes ORDER BY id')->fetchAll();
    }

    public static function trouverParSlug(string $slug): ?array
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('SELECT * FROM notes WHERE slug = ?');
        $requete->execute([$slug]);
        return $requete->fetch() ?: null;
    }

    public static function pourProduit(int $produitId): array
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare(
            'SELECT n.* FROM notes n
             JOIN produit_notes pn ON pn.note_id = n.id
             WHERE pn.produit_id = ?
             ORDER BY n.id'
        );
        $requete->execute([$produitId]);
        return $requete->fetchAll();
    }

    // ---------- Gestion dans l'admin ("Markat & notat") ----------

    // Toutes les notes, avec le nombre de parfums qui les ont
    public static function toutesAvecNombre(): array
    {
        $pdo = Database::getConnexion();
        return $pdo->query(
            'SELECT n.*, COUNT(pn.produit_id) AS nb_produits
             FROM notes n
             LEFT JOIN produit_notes pn ON pn.note_id = n.id
             GROUP BY n.id
             ORDER BY n.id'
        )->fetchAll();
    }

    public static function nomPris(string $nom, int $saufId = 0): bool
    {
        $requete = Database::getConnexion()->prepare('SELECT COUNT(*) FROM notes WHERE nom = ? AND id <> ?');
        $requete->execute([$nom, $saufId]);
        return $requete->fetchColumn() > 0;
    }

    public static function creer(string $nom): void
    {
        $requete = Database::getConnexion()->prepare('INSERT INTO notes (nom, slug) VALUES (?, ?)');
        $requete->execute([$nom, slug_libre('notes', $nom)]);
    }

    public static function renommer(int $id, string $nom): void
    {
        $requete = Database::getConnexion()->prepare('UPDATE notes SET nom = ?, slug = ? WHERE id = ?');
        $requete->execute([$nom, slug_libre('notes', $nom, $id), $id]);
    }

    // Les parfums qui avaient cette note la perdent simplement (produit_notes : ON DELETE CASCADE)
    public static function supprimer(int $id): void
    {
        $requete = Database::getConnexion()->prepare('DELETE FROM notes WHERE id = ?');
        $requete->execute([$id]);
    }

    // Remplace toutes les notes d'un produit par celles cochées dans l'admin
    public static function definirPourProduit(int $produitId, array $noteIds): void
    {
        $pdo = Database::getConnexion();
        $pdo->prepare('DELETE FROM produit_notes WHERE produit_id = ?')->execute([$produitId]);

        // On ne garde que les ids qui existent vraiment (un id inventé ferait échouer la clé étrangère)
        $existantes = array_column(self::toutes(), 'id');
        $ajout = $pdo->prepare('INSERT INTO produit_notes (produit_id, note_id) VALUES (?, ?)');
        foreach (array_unique(array_map('intval', $noteIds)) as $noteId) {
            if (in_array($noteId, $existantes, true)) {
                $ajout->execute([$produitId, $noteId]);
            }
        }
    }
}

<?php

// Codes promo : -10 % (pourcentage) ou -5 € (montant), avec conditions facultatives
class CodePromo
{
    // "expire" est calculé par MySQL (même horloge que le reste du site)
    private const SELECT = 'SELECT *, (expire_le IS NOT NULL AND expire_le < CURDATE()) AS expire FROM codes_promo';

    public static function trouver(string $code): ?array
    {
        $requete = Database::getConnexion()->prepare(self::SELECT . ' WHERE code = ?');
        $requete->execute([mb_strtoupper(trim($code))]);
        return $requete->fetch() ?: null;
    }

    /**
     * Le code peut-il être utilisé pour ce panier ?
     * Renvoie le message d'erreur à afficher au client, ou null si tout va bien.
     */
    public static function erreur(?array $code, float $sousTotal): ?string
    {
        if (!$code || !$code['actif']) {
            return 'Ky kod zbritjeje nuk ekziston.';
        }
        if ($code['expire']) {
            return 'Ky kod zbritjeje ka skaduar.';
        }
        if ($code['utilisations_max'] !== null && $code['utilisations'] >= $code['utilisations_max']) {
            return 'Ky kod zbritjeje është përdorur tashmë numrin maksimal të herëve.';
        }
        if ($code['minimum'] !== null && $sousTotal < (float) $code['minimum']) {
            return 'Ky kod vlen për porosi mbi ' . prix($code['minimum']) . '.';
        }
        return null;
    }

    // Montant de la réduction en euros (jamais plus que le panier)
    public static function remise(array $code, float $sousTotal): float
    {
        $remise = $code['type'] === 'pourcentage'
            ? $sousTotal * (float) $code['valeur'] / 100
            : (float) $code['valeur'];
        return round(min($remise, $sousTotal), 2);
    }

    // Texte court pour l'affichage : "-10 %" ou "-5,00 €"
    public static function libelle(array $code): string
    {
        return $code['type'] === 'pourcentage'
            ? '-' . (float) $code['valeur'] . ' %'
            : '-' . prix($code['valeur']);
    }

    /**
     * Au moment d'enregistrer la commande (dans sa transaction) : on revérifie le code
     * et on compte une utilisation. FOR UPDATE bloque la ligne : si deux clients commandent
     * en même temps avec le dernier code disponible, le second attend et se voit refuser.
     * Renvoie la remise ; lance une RuntimeException si le code n'est plus valable.
     */
    public static function utiliser(PDO $pdo, string $code, float $sousTotal): float
    {
        $requete = $pdo->prepare(self::SELECT . ' WHERE code = ? FOR UPDATE');
        $requete->execute([$code]);
        $ligne = $requete->fetch() ?: null;

        $erreur = self::erreur($ligne, $sousTotal);
        if ($erreur) {
            throw new RuntimeException($erreur);
        }

        $pdo->prepare('UPDATE codes_promo SET utilisations = utilisations + 1 WHERE id = ?')->execute([$ligne['id']]);
        return self::remise($ligne, $sousTotal);
    }

    // Commande annulée : l'utilisation du code est rendue
    public static function liberer(PDO $pdo, string $code): void
    {
        $pdo->prepare('UPDATE codes_promo SET utilisations = GREATEST(utilisations - 1, 0) WHERE code = ?')->execute([$code]);
    }

    // ---------- Admin ----------

    public static function tous(): array
    {
        return Database::getConnexion()->query(self::SELECT . ' ORDER BY created_at DESC')->fetchAll();
    }

    public static function creer(array $d): void
    {
        $requete = Database::getConnexion()->prepare(
            'INSERT INTO codes_promo (code, type, valeur, minimum, expire_le, utilisations_max) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $requete->execute([$d['code'], $d['type'], $d['valeur'], $d['minimum'], $d['expire_le'], $d['utilisations_max']]);
    }

    // Active <-> désactive (un code désactivé est refusé mais reste dans la liste)
    public static function basculer(int $id): void
    {
        Database::getConnexion()->prepare('UPDATE codes_promo SET actif = 1 - actif WHERE id = ?')->execute([$id]);
    }

    // Les commandes gardent leur copie du code (colonne commandes.code_promo) : rien n'est perdu
    public static function supprimer(int $id): void
    {
        Database::getConnexion()->prepare('DELETE FROM codes_promo WHERE id = ?')->execute([$id]);
    }
}

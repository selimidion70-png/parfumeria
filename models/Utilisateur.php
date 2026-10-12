<?php

class Utilisateur
{
    public static function trouverParEmail(string $email): ?array
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
        $requete->execute([$email]);
        return $requete->fetch() ?: null;
    }

    // Crée le compte et renvoie son id. Le mot de passe est haché (jamais stocké en clair).
    public static function creer(string $prenom, string $nom, string $email, string $motDePasse, ?string $telephone): int
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare(
            'INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, telephone) VALUES (?, ?, ?, ?, ?)'
        );
        $requete->execute([
            $prenom,
            $nom,
            $email,
            password_hash($motDePasse, PASSWORD_DEFAULT),
            $telephone ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    // ---------- Limite des tentatives de connexion ----------
    // On compte avec NOW() de MySQL pour ne pas dépendre du fuseau horaire de PHP.

    // Trop d'échecs récents pour cet email, ou depuis cette adresse IP ?
    // (limite IP plus haute : au Kosovo, beaucoup de clients mobiles partagent la même IP)
    public static function tropDEchecs(string $email, string $ip, int $minutes = 15): bool
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare(
            'SELECT
                SUM(email = ?) AS par_email,
                SUM(ip = ?)    AS par_ip
             FROM connexion_echecs
             WHERE created_at > NOW() - INTERVAL ? MINUTE'
        );
        $requete->execute([$email, $ip, $minutes]);
        $nb = $requete->fetch();
        return (int) $nb['par_email'] >= 5 || (int) $nb['par_ip'] >= 20;
    }

    public static function noterEchec(string $email, string $ip): void
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('INSERT INTO connexion_echecs (email, ip) VALUES (?, ?)');
        $requete->execute([mb_substr($email, 0, 150), $ip]);

        // Ménage : on ne garde pas les vieux échecs
        $pdo->exec('DELETE FROM connexion_echecs WHERE created_at < NOW() - INTERVAL 1 DAY');
    }

    public static function effacerEchecs(string $email): void
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('DELETE FROM connexion_echecs WHERE email = ?');
        $requete->execute([$email]);
    }

    // ---------- Mot de passe oublié ----------
    // Le jeton (64 caractères aléatoires) part dans l'email ; la base ne garde que son hash sha256.

    /**
     * Crée un lien de réinitialisation valable 1 heure et renvoie le jeton.
     * Renvoie null si 3 liens ont déjà été demandés dans l'heure (évite de bombarder la boîte mail).
     */
    public static function creerJetonReinitialisation(int $utilisateurId): ?string
    {
        $pdo = Database::getConnexion();

        // Ménage : les liens expirés ne servent plus à rien
        $pdo->exec('DELETE FROM reinitialisations WHERE expire_le < NOW()');

        $requete = $pdo->prepare(
            'SELECT COUNT(*) FROM reinitialisations WHERE utilisateur_id = ? AND created_at > NOW() - INTERVAL 1 HOUR'
        );
        $requete->execute([$utilisateurId]);
        if ((int) $requete->fetchColumn() >= 3) {
            return null;
        }

        $jeton = bin2hex(random_bytes(32)); // impossible à deviner
        $requete = $pdo->prepare(
            'INSERT INTO reinitialisations (utilisateur_id, jeton_hash, expire_le) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)'
        );
        $requete->execute([$utilisateurId, hash('sha256', $jeton)]);
        return $jeton;
    }

    // Renvoie l'utilisateur lié à un jeton encore valable, sinon null
    public static function trouverParJeton(string $jeton): ?array
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare(
            'SELECT u.* FROM reinitialisations r
             JOIN utilisateurs u ON u.id = r.utilisateur_id
             WHERE r.jeton_hash = ? AND r.expire_le > NOW()'
        );
        $requete->execute([hash('sha256', $jeton)]);
        return $requete->fetch() ?: null;
    }

    // Enregistre le nouveau mot de passe et supprime tous les liens de cet utilisateur (ils ne servent qu'une fois)
    public static function changerMotDePasse(int $utilisateurId, string $motDePasse): void
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
        $requete->execute([password_hash($motDePasse, PASSWORD_DEFAULT), $utilisateurId]);

        $requete = $pdo->prepare('DELETE FROM reinitialisations WHERE utilisateur_id = ?');
        $requete->execute([$utilisateurId]);
    }
}

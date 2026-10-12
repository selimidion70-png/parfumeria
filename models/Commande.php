<?php

class Commande
{
    /**
     * Enregistre la commande, ses lignes, et diminue le stock.
     * Tout se fait dans une transaction : si un article n'a plus assez de stock,
     * rien n'est enregistré et une exception est lancée.
     */
    /**
     * $code : code promo du panier (ou null). Il est revérifié ici, dans la transaction :
     * s'il n'est plus valable, la commande n'est pas enregistrée (RuntimeException).
     * Total = articles - remise + frais de livraison (gratuite au-dessus de LIVRAISON_GRATUITE_DES).
     */
    public static function creer(array $client, array $lignes, float $sousTotal, ?string $code, ?int $utilisateurId): int
    {
        $pdo = Database::getConnexion();
        $pdo->beginTransaction();

        try {
            $remise = $code ? CodePromo::utiliser($pdo, $code, $sousTotal) : 0.0;
            $total = $sousTotal - $remise + frais_livraison($client['pays'], $sousTotal - $remise);

            $requete = $pdo->prepare(
                'INSERT INTO commandes (utilisateur_id, prenom, nom, telephone, email, adresse, ville, pays, total, code_promo, remise)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $requete->execute([
                $utilisateurId,
                $client['prenom'],
                $client['nom'],
                $client['telephone'],
                $client['email'] ?: null,
                $client['adresse'],
                $client['ville'],
                $client['pays'],
                $total,
                $code,
                $remise,
            ]);
            $commandeId = (int) $pdo->lastInsertId();

            $stock = $pdo->prepare('UPDATE variantes SET stock = stock - ? WHERE id = ? AND stock >= ?');
            $detail = $pdo->prepare(
                'INSERT INTO commande_details (commande_id, variante_id, nom_produit, marque, contenance_ml, prix_unitaire, quantite)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($lignes as $ligne) {
                $stock->execute([$ligne['quantite'], $ligne['variante_id'], $ligne['quantite']]);
                if ($stock->rowCount() === 0) {
                    throw new RuntimeException('Nuk ka stok të mjaftueshëm për ' . $ligne['nom'] . ' (' . ml($ligne['contenance_ml']) . ').');
                }
                $detail->execute([
                    $commandeId,
                    $ligne['variante_id'],
                    $ligne['nom'],
                    $ligne['marque'],
                    $ligne['contenance_ml'],
                    $ligne['prix'],
                    $ligne['quantite'],
                ]);
            }

            $pdo->commit();
            return $commandeId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function trouver(int $id): ?array
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('SELECT * FROM commandes WHERE id = ?');
        $requete->execute([$id]);
        return $requete->fetch() ?: null;
    }

    public static function details(int $commandeId): array
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('SELECT * FROM commande_details WHERE commande_id = ? ORDER BY id');
        $requete->execute([$commandeId]);
        return $requete->fetchAll();
    }

    public static function parUtilisateur(int $utilisateurId): array
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare('SELECT * FROM commandes WHERE utilisateur_id = ? ORDER BY created_at DESC, id DESC');
        $requete->execute([$utilisateurId]);
        return $requete->fetchAll();
    }

    // ---------- Admin ----------

    // Toutes les commandes, éventuellement filtrées par statut, avec le nombre d'articles
    public static function toutes(?string $statut = null): array
    {
        $pdo = Database::getConnexion();
        $sql = 'SELECT c.*, (SELECT COALESCE(SUM(d.quantite), 0) FROM commande_details d WHERE d.commande_id = c.id) AS nb_articles
                FROM commandes c';
        $params = [];
        if ($statut !== null) {
            $sql .= ' WHERE c.statut = ?';
            $params[] = $statut;
        }
        $sql .= ' ORDER BY c.created_at DESC, c.id DESC';

        $requete = $pdo->prepare($sql);
        $requete->execute($params);
        return $requete->fetchAll();
    }

    // Nombre de commandes par statut : ['en_attente' => 3, 'livree' => 10, ...]
    public static function compterParStatut(): array
    {
        $pdo = Database::getConnexion();
        $resultat = array_fill_keys(array_keys(STATUTS_COMMANDE), 0);
        foreach ($pdo->query('SELECT statut, COUNT(*) AS nb FROM commandes GROUP BY statut') as $ligne) {
            $resultat[$ligne['statut']] = (int) $ligne['nb'];
        }
        return $resultat;
    }

    // Chiffre d'affaires des commandes livrées (argent réellement encaissé)
    public static function totalEncaisse(): float
    {
        $pdo = Database::getConnexion();
        return (float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut = 'livree'")->fetchColumn();
    }

    // ---------- Statistiques (page admin "Statistikat") ----------
    // Les commandes annulées ne comptent jamais dans les ventes.

    /**
     * Ventes des 12 derniers mois, du plus récent au plus ancien.
     * valeur   = total des commandes passées (même pas encore livrées)
     * encaisse = total des commandes livrées (argent réellement reçu)
     * Les mois sans commande sont présents avec 0.
     */
    public static function statsParMois(int $nbMois = 12): array
    {
        $pdo = Database::getConnexion();
        $requete = $pdo->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois,
                    COUNT(*) AS nb,
                    SUM(total) AS valeur,
                    SUM(CASE WHEN statut = 'livree' THEN total ELSE 0 END) AS encaisse
             FROM commandes
             WHERE statut <> 'annulee'
               AND created_at >= DATE_FORMAT(NOW() - INTERVAL ? MONTH, '%Y-%m-01')
             GROUP BY mois"
        );
        $requete->execute([$nbMois - 1]);
        $parMois = array_column($requete->fetchAll(), null, 'mois');

        // On construit la liste complète des mois (sinon un mois sans vente disparaîtrait)
        $resultat = [];
        $mois = new DateTime('first day of this month');
        for ($i = 0; $i < $nbMois; $i++) {
            $cle = $mois->format('Y-m');
            $resultat[] = [
                'mois'     => $cle,
                'nb'       => (int) ($parMois[$cle]['nb'] ?? 0),
                'valeur'   => (float) ($parMois[$cle]['valeur'] ?? 0),
                'encaisse' => (float) ($parMois[$cle]['encaisse'] ?? 0),
            ];
            $mois->modify('-1 month');
        }
        return $resultat;
    }

    // Les parfums les plus vendus (en nombre de flacons), toutes contenances confondues
    public static function plusVendus(int $limite = 10): array
    {
        $pdo = Database::getConnexion();
        return $pdo->query(
            "SELECT d.nom_produit, d.marque,
                    SUM(d.quantite) AS quantite,
                    SUM(d.quantite * d.prix_unitaire) AS montant
             FROM commande_details d
             JOIN commandes c ON c.id = d.commande_id
             WHERE c.statut <> 'annulee'
             GROUP BY d.nom_produit, d.marque
             ORDER BY quantite DESC, montant DESC
             LIMIT " . (int) $limite
        )->fetchAll();
    }

    // Nombre et valeur des commandes par pays (tous les pays de livraison, même à 0)
    public static function statsParPays(): array
    {
        $pdo = Database::getConnexion();
        $resultat = [];
        foreach (array_keys(PAYS_LIVRAISON) as $pays) {
            $resultat[$pays] = ['nb' => 0, 'valeur' => 0.0];
        }
        $lignes = $pdo->query(
            "SELECT pays, COUNT(*) AS nb, SUM(total) AS valeur FROM commandes WHERE statut <> 'annulee' GROUP BY pays"
        );
        foreach ($lignes as $ligne) {
            $resultat[$ligne['pays']] = ['nb' => (int) $ligne['nb'], 'valeur' => (float) $ligne['valeur']];
        }
        return $resultat;
    }

    // Chiffres globaux : nombre de commandes, panier moyen, part des annulations
    public static function statsResume(): array
    {
        $pdo = Database::getConnexion();
        $ligne = $pdo->query(
            "SELECT COUNT(*) AS total,
                    SUM(statut = 'annulee') AS annulees,
                    AVG(CASE WHEN statut <> 'annulee' THEN total END) AS panier_moyen
             FROM commandes"
        )->fetch();

        $total = (int) $ligne['total'];
        return [
            'commandes'    => $total - (int) $ligne['annulees'],
            'panier_moyen' => (float) $ligne['panier_moyen'],
            // Pourcentage d'annulation ; 0 s'il n'y a encore aucune commande (pas de division par 0)
            'taux_annulation' => $total ? (int) round($ligne['annulees'] / $total * 100) : 0,
        ];
    }

    /**
     * Change le statut d'une commande.
     * Une commande annulée est définitive : son stock est remis en vente,
     * et on ne peut plus changer son statut ensuite.
     */
    public static function changerStatut(int $id, string $nouveauStatut): void
    {
        $pdo = Database::getConnexion();
        $pdo->beginTransaction();

        try {
            // FOR UPDATE : bloque la ligne pour éviter deux annulations en même temps
            $requete = $pdo->prepare('SELECT statut, code_promo FROM commandes WHERE id = ? FOR UPDATE');
            $requete->execute([$id]);
            $ligne = $requete->fetch();
            $ancienStatut = $ligne ? $ligne['statut'] : false;

            if ($ancienStatut === false) {
                throw new RuntimeException('Porosia nuk ekziston.');
            }
            if ($ancienStatut === 'annulee') {
                throw new RuntimeException('Një porosi e anuluar nuk mund të ndryshohet më.');
            }

            $requete = $pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?');
            $requete->execute([$nouveauStatut, $id]);

            // Annulation : on remet les articles en stock
            if ($nouveauStatut === 'annulee') {
                $requete = $pdo->prepare(
                    'UPDATE variantes v
                     JOIN commande_details d ON d.variante_id = v.id
                     SET v.stock = v.stock + d.quantite
                     WHERE d.commande_id = ?'
                );
                $requete->execute([$id]);

                // ... et le code promo peut resservir
                if ($ligne['code_promo']) {
                    CodePromo::liberer($pdo, $ligne['code_promo']);
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

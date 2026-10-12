<?php

// Emails envoyés par le site.
// En local, Laragon les attrape dans Mailpit (http://localhost:8025) : rien ne part vraiment.
class Notification
{
    /**
     * Prévient le cousin qu'une commande vient d'arriver.
     * Renvoie false si l'envoi a échoué : la commande reste enregistrée quand même,
     * elle sera visible dans l'admin.
     */
    public static function nouvelleCommande(int $commandeId): bool
    {
        $commande = Commande::trouver($commandeId);
        if (!$commande || EMAIL_COMMANDES === '') {
            return false;
        }

        $lignes = [];
        foreach (Commande::details($commandeId) as $d) {
            $lignes[] = '- ' . $d['quantite'] . ' × ' . ($d['marque'] ? $d['marque'] . ' ' : '') . $d['nom_produit'] . ' (' . ml($d['contenance_ml']) . ') = ' . prix($d['prix_unitaire'] * $d['quantite']);
        }

        // Le texte de l'email (en albanais, c'est le cousin qui le lit)
        $message = 'Porosi e re nr. ' . $commandeId . "\n\n"
            . 'Klienti: ' . $commande['prenom'] . ' ' . $commande['nom'] . "\n"
            . 'Telefoni: ' . $commande['telephone'] . "\n"
            . ($commande['email'] ? 'Email: ' . $commande['email'] . "\n" : '')
            . 'Adresa: ' . $commande['adresse'] . ', ' . $commande['ville'] . ', ' . $commande['pays'] . "\n\n"
            . "Artikujt:\n" . implode("\n", $lignes) . "\n"
            . ($commande['remise'] > 0 ? 'Zbritja (kodi ' . $commande['code_promo'] . '): -' . prix($commande['remise']) . "\n" : '')
            . "\n"
            . 'Totali për t\'u arkëtuar: ' . prix($commande['total']) . "\n\n"
            . "Telefononi klientin për ta konfirmuar, pastaj ndryshoni statusin këtu:\n"
            . url_absolue('admin_commande', ['id' => $commandeId]) . "\n";

        // Sujet : seulement des valeurs sûres (numéro + total), jamais un texte tapé par le client,
        // car un retour à la ligne dans un en-tête permettrait d'ajouter d'autres destinataires
        $sujet = 'Porosi e re nr. ' . $commandeId . ' — ' . prix($commande['total']);

        // "Répondre" écrit directement au client (son email a été vérifié avec filter_var)
        return self::envoyer(EMAIL_COMMANDES, $sujet, $message, $commande['email'] ?: null);
    }

    /**
     * Envoie au client le lien pour choisir un nouveau mot de passe.
     * $jeton est le jeton en clair (celui qui va dans le lien), jamais stocké tel quel en base.
     */
    public static function reinitialisationMotDePasse(array $utilisateur, string $jeton): bool
    {
        $message = 'Përshëndetje ' . $utilisateur['prenom'] . ",\n\n"
            . 'Kemi marrë një kërkesë për të ndryshuar fjalëkalimin e llogarisë suaj në ' . NOM_SITE . ".\n"
            . "Klikoni lidhjen më poshtë për të zgjedhur një fjalëkalim të ri (lidhja vlen 1 orë):\n\n"
            . url_absolue('nouveau_mot_de_passe', ['jeton' => $jeton]) . "\n\n"
            . "Nëse nuk e keni bërë ju këtë kërkesë, injorojeni këtë email: fjalëkalimi juaj nuk ndryshon.\n";

        return self::envoyer($utilisateur['email'], 'Ndryshimi i fjalëkalimit — ' . NOM_SITE, $message);
    }

    // Envoi commun à tous les emails du site (texte simple en UTF-8)
    private static function envoyer(string $destinataire, string $sujet, string $message, ?string $repondreA = null): bool
    {
        $entetes = [
            'From'         => mb_encode_mimeheader(NOM_SITE, 'UTF-8') . ' <' . EMAIL_EXPEDITEUR . '>',
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];
        if ($repondreA) {
            $entetes['Reply-To'] = $repondreA;
        }

        // Le @ cache l'avertissement PHP si le serveur mail ne répond pas ; on le note dans le journal
        $envoye = @mail($destinataire, mb_encode_mimeheader($sujet, 'UTF-8'), $message, $entetes);
        if (!$envoye) {
            error_log('Email "' . $sujet . '" non envoyé à ' . $destinataire);
        }
        return $envoye;
    }
}

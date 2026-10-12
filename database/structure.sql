-- ============================================================
--  Parfumeria — structure de la base (tables + catégories, marques et notes)
--  Utilisé par :
--   - Laragon          (importer ce fichier, puis exemples.sql pour avoir des parfums de test)
--   - l'hébergement     (à importer tel quel dans phpMyAdmin, voir MISE_EN_LIGNE.md)
-- ============================================================

SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS commande_details;
DROP TABLE IF EXISTS commandes;
DROP TABLE IF EXISTS produit_notes;
DROP TABLE IF EXISTS variantes;
DROP TABLE IF EXISTS produits;
DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS marques;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS connexion_echecs;
DROP TABLE IF EXISTS reinitialisations;
DROP TABLE IF EXISTS produit_images;
DROP TABLE IF EXISTS codes_promo;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Catégories (menu principal : Meshkuj / Femra / Unisex)
CREATE TABLE categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    nom  VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Marques (créées automatiquement depuis l'admin quand on tape un nouveau nom)
CREATE TABLE marques (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    nom  VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Notes olfactives ("Zgjidh sipas aromës" sur l'accueil)
CREATE TABLE notes (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    nom  VARCHAR(60) NOT NULL,
    slug VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Produits (le prix est sur chaque contenance, dans "variantes")
CREATE TABLE produits (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    marque_id    INT DEFAULT NULL,
    nom          VARCHAR(150) NOT NULL,
    description  TEXT,
    image        VARCHAR(255) DEFAULT NULL,
    actif        TINYINT(1) NOT NULL DEFAULT 1,
    en_vedette   TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = section "Më të preferuarat"
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_produit_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id),
    CONSTRAINT fk_produit_marque FOREIGN KEY (marque_id) REFERENCES marques(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Notes d'un produit (un parfum peut avoir plusieurs notes)
CREATE TABLE produit_notes (
    produit_id INT NOT NULL,
    note_id    INT NOT NULL,
    PRIMARY KEY (produit_id, note_id),
    CONSTRAINT fk_pn_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    CONSTRAINT fk_pn_note FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Variantes = contenances (30 ml, 50 ml, 100 ml...), chacune avec son prix et son stock
CREATE TABLE variantes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    produit_id    INT NOT NULL,
    contenance_ml SMALLINT NOT NULL,
    prix          DECIMAL(8,2) NOT NULL,
    prix_ancien   DECIMAL(8,2) DEFAULT NULL,       -- rempli et plus haut que prix = en promo
    stock         INT NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_variante (produit_id, contenance_ml),
    CONSTRAINT fk_variante_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Utilisateurs (comptes clients ; est_admin = 1 pour l'espace admin)
CREATE TABLE utilisateurs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    prenom       VARCHAR(100) NOT NULL,
    nom          VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone    VARCHAR(30) DEFAULT NULL,
    est_admin    TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Commandes (paiement à la livraison)
CREATE TABLE commandes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT DEFAULT NULL,
    prenom         VARCHAR(100) NOT NULL,
    nom            VARCHAR(100) NOT NULL,
    telephone      VARCHAR(30) NOT NULL,
    email          VARCHAR(150) DEFAULT NULL,
    adresse        VARCHAR(255) NOT NULL,
    ville          VARCHAR(100) NOT NULL,
    pays           ENUM('Kosovë','Shqipëri','Maqedonia e Veriut') NOT NULL,
    total          DECIMAL(9,2) NOT NULL,             -- articles - remise + livraison
    code_promo     VARCHAR(30) DEFAULT NULL,          -- copie du code utilisé (reste même si le code est supprimé)
    remise         DECIMAL(9,2) NOT NULL DEFAULT 0,   -- montant de la réduction en euros
    statut         ENUM('en_attente','confirmee','expediee','livree','annulee') NOT NULL DEFAULT 'en_attente',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_commande_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Détails des commandes (copie du nom/marque/contenance : reste juste même si le produit change)
CREATE TABLE commande_details (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    commande_id   INT NOT NULL,
    variante_id   INT DEFAULT NULL,
    nom_produit   VARCHAR(150) NOT NULL,
    marque        VARCHAR(100) DEFAULT NULL,
    contenance_ml SMALLINT NOT NULL,
    prix_unitaire DECIMAL(8,2) NOT NULL,
    quantite      INT NOT NULL,
    CONSTRAINT fk_detail_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    CONSTRAINT fk_detail_variante FOREIGN KEY (variante_id) REFERENCES variantes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Mots de passe ratés (limite des tentatives de connexion)
CREATE TABLE connexion_echecs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL,
    ip         VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email, created_at),
    INDEX idx_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Liens "mot de passe oublié" (valables 1 heure)
--     On stocke le hash du jeton, pas le jeton lui-même : même si quelqu'un lit la base,
--     il ne peut pas reconstruire le lien envoyé par email.
CREATE TABLE reinitialisations (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    jeton_hash     CHAR(64) NOT NULL UNIQUE,      -- sha256 du jeton
    expire_le      DATETIME NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reinit_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Photos supplémentaires d'un parfum (galerie de la fiche produit)
--     La photo principale reste dans produits.image (cartes, panier...) ; ici, les autres.
CREATE TABLE produit_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    fichier    VARCHAR(255) NOT NULL,              -- nom du fichier dans public/img/produits/
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_image_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Codes promo (tapés par le client dans le panier)
CREATE TABLE codes_promo (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(30) NOT NULL UNIQUE,              -- toujours en MAJUSCULES, ex : VERA10
    type             ENUM('pourcentage','montant') NOT NULL,   -- -10 % ou -5 €
    valeur           DECIMAL(8,2) NOT NULL,
    minimum          DECIMAL(8,2) DEFAULT NULL,                -- montant minimum du panier (NULL = aucun)
    expire_le        DATE DEFAULT NULL,                        -- dernier jour valable (NULL = sans fin)
    utilisations_max INT DEFAULT NULL,                         -- NULL = illimité
    utilisations     INT NOT NULL DEFAULT 0,                   -- commandes (non annulées) qui l'ont utilisé
    actif            TINYINT(1) NOT NULL DEFAULT 1,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Données de base (aussi utiles en ligne)
-- ============================================================

INSERT INTO categories (id, nom, slug) VALUES
(1, 'Meshkuj', 'meshkuj'),
(2, 'Femra',   'femra'),
(3, 'Unisex',  'unisex');

INSERT INTO notes (id, nom, slug) VALUES
(1, 'Vanilje',     'vanilje'),
(2, 'Agrume',      'agrume'),
(3, 'Lule',        'lule'),
(4, 'Dru',         'dru'),
(5, 'Oud',         'oud'),
(6, 'Të freskëta', 'te-fresketa'),
(7, 'Të ëmbla',    'te-embla'),
(8, 'Erëza',       'ereza');

# 🌸 Parfumeria — Boutique de parfums en ligne

Boutique en ligne de parfums développée en **PHP 8** avec une architecture **MVC maison** (sans framework) et une base de données **MySQL**.
Le site s'adresse à une clientèle du Kosovo, d'Albanie et de Macédoine du Nord : tous les textes visibles par les clients et l'administrateur sont en **albanais**.

> Projet réel en cours de développement pour un petit commerce. Les marques et parfums de la base d'exemple sont inventés.

---

## ✨ Fonctionnalités

**Côté client**
- Catalogue par catégorie (hommes, femmes, unisexe) avec recherche, filtres par marque, par note olfactive et par promotion, et tri (prix, nouveautés…)
- Fiche produit avec **plusieurs contenances** (ml) : chaque contenance a son propre prix, son ancien prix (promo) et son stock
- Galerie de photos par produit
- Panier, **codes promo** (pourcentage ou montant fixe, minimum d'achat, date d'expiration, nombre d'utilisations)
- Liste d'envies (favoris)
- Commande avec **paiement à la livraison** et frais de livraison selon le pays (livraison gratuite à partir d'un montant)
- Compte client : inscription, connexion, historique des commandes, **mot de passe oublié** par email
- Pages contact, livraison, retours et politique de confidentialité ; boutons WhatsApp / Viber

**Espace administrateur**
- Tableau de bord et **statistiques** des ventes
- Gestion des produits, des contenances, du stock et des photos
- Gestion des marques et des notes olfactives
- Suivi des commandes (changement de statut, **bon de commande imprimable**)
- Gestion des codes promo
- Email d'alerte à chaque nouvelle commande

---

## 🔒 Sécurité

- Requêtes préparées PDO partout (pas d'injection SQL)
- Échappement de tout ce qui est affiché (protection XSS)
- **Jeton CSRF** sur chaque formulaire POST
- Mots de passe hachés (`password_hash`), limitation des tentatives de connexion
- Mot de passe oublié : jeton haché en base, valable 1 h, usage unique, nombre de demandes limité
- Session sécurisée et en-têtes de sécurité HTTP
- Code promo revérifié dans une transaction au moment de la commande
- Photos envoyées : type vérifié, aucun script exécutable dans le dossier d'upload
- Réglages secrets (mot de passe de la base en ligne) dans un fichier ignoré par Git

---

## 🛠️ Technologies

| Côté | Outils |
|------|--------|
| Front-end | HTML5, CSS3 (variables CSS), JavaScript |
| Back-end | PHP 8, architecture MVC, PDO |
| Base de données | MySQL / MariaDB |
| Environnement | Laragon, Mailpit (emails en local), Git |

---

## 📁 Structure

```
parfumeria.ks/
├── index.php          ← routeur (index.php?page=...), session, sécurité
├── config/            ← réglages du site et connexion à la base
├── controllers/       ← accueil, produits, panier, commande, comptes, favoris, admin
├── models/            ← Produit, Marque, Note, Commande, CodePromo, Utilisateur…
├── views/             ← une vue par page + layout, partials, admin
├── public/            ← CSS et photos des produits
└── database/          ← structure.sql + exemples.sql
```

---

## 🚀 Installation en local

1. Cloner le projet dans le dossier `www` de **Laragon** :
   ```bash
   git clone https://github.com/selimidion70-png/parfumeria.git parfumeria.ks
   ```
2. Créer une base de données `parfumeria` dans phpMyAdmin
3. Importer `database/structure.sql`, puis `database/exemples.sql` (parfums d'exemple)
4. Ouvrir **http://parfumeria.ks.test** (ou `http://localhost/parfumeria.ks/`)
5. Créer un compte sur le site, puis lui donner l'accès admin :
   ```sql
   UPDATE utilisateurs SET est_admin = 1 WHERE email = 'votre.email@exemple.com';
   ```

La mise en ligne chez un hébergeur est expliquée pas à pas dans [`MISE_EN_LIGNE.md`](MISE_EN_LIGNE.md).

---

## 👤 Auteur

**Dion Selimi** — développeur web front-end en formation à l'EFP Bruxelles

- Portfolio : [selimidion70-png.github.io](https://selimidion70-png.github.io)
- GitHub : [@selimidion70-png](https://github.com/selimidion70-png)

-- ============================================================
--  Parfumeria — produits d'EXEMPLE (seulement pour tester sur Laragon)
--  À importer APRÈS structure.sql. Ne pas importer en ligne.
--  Marques et parfums inventés : à remplacer par les vrais depuis l'admin.
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO marques (id, nom, slug) VALUES
(1, 'Atelier Noir', 'atelier-noir'),
(2, 'Velours',      'velours'),
(3, 'Citrine',      'citrine'),
(4, 'Maison Ambre', 'maison-ambre');

INSERT INTO produits (id, categorie_id, marque_id, nom, description, en_vedette, created_at) VALUES
(1,  1, 1, 'Noir Intense',   'Aromë e thellë drusore me nota erëzash. Për mbrëmje dhe raste të veçanta.', 1, NOW() - INTERVAL 40 DAY),
(2,  1, 3, 'Citrine Sport',  'E freskët dhe energjike, me limon dhe portokall. Ideale për verë.',        0, NOW() - INTERVAL 30 DAY),
(3,  1, 4, 'Ambre Royal',    'Oud dhe ambër, aromë e fortë që zgjat gjithë ditën.',                     1, NOW() - INTERVAL 3 DAY),
(4,  2, 2, 'Velours Rose',   'Trëndafil dhe jasemin mbi një bazë të butë vaniljeje.',                  1, NOW() - INTERVAL 25 DAY),
(5,  2, 2, 'Velours Sucré',  'Aromë e ëmbël me vanilje dhe karamel. E ngrohtë dhe elegante.',           0, NOW() - INTERVAL 2 DAY),
(6,  2, 3, 'Fleur d\'Été',   'Lule të bardha dhe agrume, e lehtë për përditë.',                        0, NOW() - INTERVAL 15 DAY),
(7,  3, 4, 'Oud Absolu',     'Oud i pastër me dru santali. Për ata që duan të vërehen.',                1, NOW() - INTERVAL 1 DAY),
(8,  3, 1, 'Blanc Musc',     'Musk i bardhë, i pastër dhe minimal, për çdo ditë.',                      0, NOW() - INTERVAL 20 DAY),
(9,  3, 3, 'Néroli Frais',   'Lule portokalli dhe nota të freskëta detare.',                            0, NOW() - INTERVAL 5 DAY),
(10, 1, 1, 'Tabac Velvet',   'Duhan i ëmbël, vanilje dhe erëza. Aromë dimri.',                          0, NOW() - INTERVAL 10 DAY);

-- Contenances : prix, ancien prix (promo) et stock
INSERT INTO variantes (produit_id, contenance_ml, prix, prix_ancien, stock) VALUES
(1, 50, 49.90, NULL, 6),   (1, 100, 74.90, 89.90, 4),
(2, 50, 34.90, NULL, 10),  (2, 100, 49.90, NULL, 8),
(3, 100, 89.90, NULL, 3),
(4, 30, 29.90, NULL, 7),   (4, 50, 44.90, 59.90, 5),  (4, 100, 69.90, NULL, 2),
(5, 50, 39.90, NULL, 9),   (5, 100, 59.90, NULL, 6),
(6, 50, 24.90, 39.90, 12),
(7, 100, 99.90, 129.90, 2),
(8, 50, 34.90, NULL, 0),   (8, 100, 54.90, NULL, 0),
(9, 50, 32.90, NULL, 5),   (9, 100, 47.90, NULL, 4),
(10, 100, 64.90, NULL, 5);

-- Notes olfactives de chaque parfum
INSERT INTO produit_notes (produit_id, note_id) VALUES
(1,4),(1,8), (2,2),(2,6), (3,5),(3,4), (4,3),(4,1), (5,1),(5,7),
(6,3),(6,2), (7,5),(7,4), (8,6),(8,3), (9,2),(9,6), (10,1),(10,8),(10,7);

-- Migration 003 — Index de support pour le filtrage par zone et l'agrégation cron
--
-- À exécuter une fois sur la base de données.

-- Filtrage par ville (get_carte.php, get_produit.php, get_carte_produit.php
-- quand aucune bbox de viewport n'est fournie)
CREATE INDEX IF NOT EXISTS idx_pharmacies_ville ON pharmacies (ville);

-- Filtrage par zone (bounding box lat/long du viewport de la carte)
CREATE INDEX IF NOT EXISTS idx_pharmacies_latitude  ON pharmacies (latitude);
CREATE INDEX IF NOT EXISTS idx_pharmacies_longitude ON pharmacies (longitude);

-- Jointures fréquentes stocks <-> pharmacies / produits (utilisées par
-- pratiquement tous les endpoints de recherche)
CREATE INDEX IF NOT EXISTS idx_stocks_id_pharmacie ON stocks (id_pharmacie);
CREATE INDEX IF NOT EXISTS idx_stocks_id_produit    ON stocks (id_produit);

-- Utilisé par le cron d'agrégation (database/cron/recalculer_produits_populaires.php)
-- pour grouper les réservations par produit sans scanner une table non indexée
CREATE INDEX IF NOT EXISTS idx_reservations_id_stock ON reservations (id_stock);

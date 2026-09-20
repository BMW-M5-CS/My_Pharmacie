-- Migration 002 — Index trigramme pour la recherche de produits
--
-- Les recherches ILIKE '%motif%' (get_carte_produit.php, produit.php) ne
-- peuvent pas utiliser un index B-Tree classique à cause du '%' en tête de
-- motif. L'extension pg_trgm permet un index GIN qui accélère ce type de
-- recherche même avec un motif au milieu du texte.
--
-- À exécuter une fois sur la base de données (nécessite les droits
-- d'installation d'extension — généralement disponible par défaut sur les
-- hébergeurs PostgreSQL gérés ; sinon demander à l'hébergeur de l'activer).

CREATE EXTENSION IF NOT EXISTS pg_trgm;

CREATE INDEX IF NOT EXISTS idx_produits_nom_medicament_trgm
    ON produits USING GIN (nom_medicament gin_trgm_ops);

-- Migration 001 — Table agrégée des produits les plus réservés
--
-- Remplace le calcul en direct (GROUP BY sur toute la table `reservations`,
-- exécuté à chaque affichage de chaque fiche pharmacie) par une lecture d'une
-- petite table pré-calculée, rafraîchie périodiquement par un cron
-- (voir database/cron/recalculer_produits_populaires.php).
--
-- À exécuter une fois sur la base de données (pgAdmin ou psql).

CREATE TABLE IF NOT EXISTS statistiques_produits_populaires (
    id_produit      INTEGER PRIMARY KEY REFERENCES produits(id_produit) ON DELETE CASCADE,
    nb_reservations INTEGER NOT NULL DEFAULT 0,
    calcule_le      TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Permet de trier rapidement "les plus réservés en premier" lors de la lecture
CREATE INDEX IF NOT EXISTS idx_statistiques_produits_populaires_nb
    ON statistiques_produits_populaires (nb_reservations DESC);

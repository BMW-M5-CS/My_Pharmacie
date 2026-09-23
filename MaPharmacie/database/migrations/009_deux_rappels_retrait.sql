-- ============================================================================
-- Migration 009 — Deux rappels client avant expiration du retrait
-- ----------------------------------------------------------------------------
-- Avant : un seul rappel (1h avant expiration), suivi par une simple case
-- booléenne rappel_retrait_envoye (envoyé / pas envoyé) — migration 007.
--
-- Après : deux rappels (2h restantes, puis 1h restante avant l'expiration
-- des 5h de retrait). Une case booléenne ne peut compter que jusqu'à 1 ; il
-- faut donc un compteur, exactement le même principe que
-- nb_relances_envoyees déjà utilisé pour les 3 rappels du pharmacien
-- (migration 005).
--
-- Sûre à rejouer (IF NOT EXISTS partout).
-- ============================================================================

BEGIN;

-- ----- 1. Nouveau compteur -----

ALTER TABLE reservations
    ADD COLUMN IF NOT EXISTS nb_rappels_retrait_envoyes SMALLINT NOT NULL DEFAULT 0;

-- ----- 2. Garde-fou de cohérence -----
-- Jamais plus de 2 rappels envoyés (les 2 rappels prévus : 2h puis 1h restantes).

ALTER TABLE reservations
    DROP CONSTRAINT IF EXISTS chk_reservations_nb_rappels_retrait;
ALTER TABLE reservations
    ADD CONSTRAINT chk_reservations_nb_rappels_retrait
    CHECK (nb_rappels_retrait_envoyes BETWEEN 0 AND 2);

-- ----- 3. Retrait de l'ancienne case, devenue inutile -----

DROP INDEX IF EXISTS idx_reservations_rappel_retrait;

ALTER TABLE reservations
    DROP COLUMN IF EXISTS rappel_retrait_envoye;

-- ----- 4. Nouvel index, sur le même principe que idx_reservations_demandes_en_cours -----
-- Le cron de rappel scanne régulièrement les réservations confirmées pas
-- encore au maximum de rappels : un index dédié évite de parcourir toute la
-- table à l'échelle.

CREATE INDEX IF NOT EXISTS idx_reservations_rappel_retrait
    ON reservations (statut, expire_at, nb_rappels_retrait_envoyes)
    WHERE statut = 'confirmee' AND nb_rappels_retrait_envoyes < 2;

COMMIT;
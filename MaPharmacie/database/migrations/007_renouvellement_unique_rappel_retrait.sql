-- ============================================================================
-- Migration 007 — Renouvellement unique + rappel avant expiration du retrait
-- ----------------------------------------------------------------------------
-- Deux ajustements demandés après coup :
--   1. Une réservation ne peut être renouvelée qu'UNE SEULE FOIS. La demande
--      issue d'un renouvellement n'est elle-même plus renouvelable si elle
--      expire à son tour.
--   2. Rappel automatique 1h avant l'expiration d'une réservation CONFIRMÉE
--      (délai de retrait de 5h, inchangé) — pour éviter qu'un client oublie.
-- ============================================================================

BEGIN;

ALTER TABLE reservations
    -- TRUE pour une réservation "normale" (peut être renouvelée une fois si
    -- elle expire). FALSE pour une demande issue d'un renouvellement : elle
    -- ne peut plus être renouvelée à son tour.
    ADD COLUMN IF NOT EXISTS renouvelable BOOLEAN NOT NULL DEFAULT TRUE,

    -- Empêche le cron de rappel d'envoyer deux fois le même email si son
    -- exécution chevauche la fenêtre d'1h plusieurs fois de suite.
    ADD COLUMN IF NOT EXISTS rappel_retrait_envoye BOOLEAN NOT NULL DEFAULT FALSE;

-- Index dédié pour que le cron de rappel ne scanne pas toute la table.
CREATE INDEX IF NOT EXISTS idx_reservations_rappel_retrait
    ON reservations (statut, expire_at, rappel_retrait_envoye)
    WHERE statut = 'confirmee' AND rappel_retrait_envoye = FALSE;

COMMIT;

-- ============================================================================
-- Migration 008 — Compte "administration plateforme" + désactivation de pharmacie
-- ----------------------------------------------------------------------------
-- Ajoute le rôle qui remplace l'ancien "Ministère" dans la hiérarchie (pivot
-- déjà acté : Client → Pharmacien → Administration plateforme, un rôle
-- interne, plus lié à aucune autorité extérieure).
--
-- Ce compte n'est PAS rattaché à une pharmacie précise (id_pharmacie NULL
-- sur administrateurs, déjà prévu nullable depuis la migration 006) : il
-- peut consulter et gérer les réservations de N'IMPORTE QUELLE pharmacie.
--
-- Ajoute aussi un statut actif/désactivé sur les pharmacies (spec §3.2) :
-- une pharmacie désactivée disparaît des recherches et listes publiques,
-- mais son compte pharmacien garde l'accès pour clôturer les réservations
-- en cours — jamais de suppression physique des données.
-- ============================================================================

BEGIN;

-- ----- 1. Nouveau rôle -----

INSERT INTO roles (nom_role, description)
VALUES ('administration_plateforme', 'Gère l''ensemble des pharmacies et leurs réservations, en interne (Wilfried / équipe MaPharmacie).')
ON CONFLICT (nom_role) DO NOTHING;


-- ----- 2. Statut des pharmacies -----

ALTER TABLE pharmacies
    ADD COLUMN IF NOT EXISTS statut VARCHAR(20) NOT NULL DEFAULT 'active';

ALTER TABLE pharmacies
    DROP CONSTRAINT IF EXISTS chk_pharmacies_statut;
ALTER TABLE pharmacies
    ADD CONSTRAINT chk_pharmacies_statut
    CHECK (statut IN ('active', 'desactivee'));

COMMIT;

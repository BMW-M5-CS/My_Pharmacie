-- ============================================================================
-- Migration 004 — Canaux de récupération dédiés (email / téléphone)
-- ----------------------------------------------------------------------------
-- Remplace le champ unique "contact_recuperation" (texte libre, email OU
-- téléphone, deviné au moment de l'usage) par deux colonnes typées et
-- validées séparément. Objectif : le système sait avec certitude quels
-- canaux existent pour un compte, au lieu de deviner via une regex à chaque
-- utilisation (mot de passe oublié, confirmation de réservation, etc.).
--
-- Ajoute aussi une colonne "canal" sur reset_tokens, pour que la table soit
-- prête à distinguer un token envoyé par email (lien cliquable) d'un futur
-- code envoyé par SMS (code court à saisir), sans nouvelle migration le jour
-- où le SMS sera activé.
--
-- Sûre à rejouer (IF NOT EXISTS partout) — peut être exécutée plusieurs fois
-- sans erreur.
-- ============================================================================

BEGIN;

-- ----- 1. Nouvelles colonnes sur users -----

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_recuperation VARCHAR(255),
    ADD COLUMN IF NOT EXISTS telephone_recuperation VARCHAR(20);

-- Contraintes de format, pour ne jamais stocker n'importe quoi dans ces colonnes
-- (contrairement à contact_recuperation qui ne portait aucune contrainte en base,
-- seulement une validation applicative facile à contourner en cas d'oubli).

ALTER TABLE users
    DROP CONSTRAINT IF EXISTS chk_email_recuperation_format;
ALTER TABLE users
    ADD CONSTRAINT chk_email_recuperation_format
    CHECK (email_recuperation IS NULL OR email_recuperation ~* '^[^@\s]+@[^@\s]+\.[^@\s]+$');

ALTER TABLE users
    DROP CONSTRAINT IF EXISTS chk_telephone_recuperation_format;
ALTER TABLE users
    ADD CONSTRAINT chk_telephone_recuperation_format
    CHECK (telephone_recuperation IS NULL OR telephone_recuperation ~ '^[0-9+\s]{8,20}$');


-- ----- 2. Migration des données existantes -----
-- Reprend exactement la même règle de détection que celle déjà écrite (et
-- jusqu'ici jamais persistée) dans traite_inscrit.php / traite_profil.php.

UPDATE users
SET email_recuperation = contact_recuperation
WHERE contact_recuperation IS NOT NULL
  AND contact_recuperation ~* '^[^@\s]+@[^@\s]+\.[^@\s]+$'
  AND email_recuperation IS NULL;

UPDATE users
SET telephone_recuperation = contact_recuperation
WHERE contact_recuperation IS NOT NULL
  AND contact_recuperation ~ '^[0-9+\s]{8,20}$'
  AND telephone_recuperation IS NULL;


-- ----- 3. Suppression de l'ancienne colonne ambiguë -----

ALTER TABLE users
    DROP COLUMN IF EXISTS contact_recuperation;


-- ----- 4. Colonne "canal" sur reset_tokens -----

ALTER TABLE reset_tokens
    ADD COLUMN IF NOT EXISTS canal VARCHAR(10) NOT NULL DEFAULT 'email';

ALTER TABLE reset_tokens
    DROP CONSTRAINT IF EXISTS chk_reset_tokens_canal;
ALTER TABLE reset_tokens
    ADD CONSTRAINT chk_reset_tokens_canal
    CHECK (canal IN ('email', 'sms'));

COMMIT;

-- ============================================================================
-- Migration 005 — Circuit de validation pharmacien pour les réservations
-- ----------------------------------------------------------------------------
-- Avant : une réservation est acceptée automatiquement (statut "en_attente"
-- dès la demande du client), sans regard du pharmacien.
--
-- Après : le client crée une DEMANDE ("demandee"), le pharmacien doit
-- explicitement l'accepter ("confirmee") ou la rejeter ("rejetee"). Le stock
-- n'est bloqué qu'à l'acceptation, jamais à la demande.
--
-- Nouveau cycle de vie complet :
--   demandee  → confirmee → annulee / expiree / renouvele
--             → rejetee
--             → expiree   (le pharmacien n'a pas répondu sous 12h)
--
-- Sûre à rejouer (IF NOT EXISTS partout).
-- ============================================================================

-- ----- PARTIE À EXÉCUTER SÉPARÉMENT, EN PREMIER -----
-- IMPORTANT : la case "statut" de cette table a déjà, dans la vraie base de
-- données, une liste fermée de mots autorisés à l'avance (ce qu'on appelle un
-- "type enum" en PostgreSQL) — pas une case de texte libre. On ne peut donc
-- pas lui ajouter une règle de validation classique : il faut directement
-- ajouter les deux nouveaux mots à la liste déjà existante.
--
-- ⚠️ CES DEUX LIGNES DOIVENT ÊTRE EXÉCUTÉES SÉPARÉMENT, AVANT TOUT LE RESTE DE
-- CE FICHIER (avant le bloc BEGIN...COMMIT plus bas) — PostgreSQL interdit
-- d'utiliser un mot tout juste ajouté à la liste dans la même exécution que
-- celle qui l'a ajouté. Sélectionne uniquement ces deux lignes dans pgAdmin
-- et exécute-les à part, avant de lancer le reste du fichier.

ALTER TYPE reservation_status ADD VALUE IF NOT EXISTS 'demandee';
ALTER TYPE reservation_status ADD VALUE IF NOT EXISTS 'rejetee';

-- ----- Une fois les deux lignes ci-dessus exécutées séparément, continue avec tout ce qui suit -----

BEGIN;

-- ----- 1. Nouvelles colonnes -----

ALTER TABLE reservations
    -- NULL tant que le pharmacien n'a pas encore statué. Permet de distinguer
    -- une expiration "faute du pharmacien" (colonne restée NULL) d'une
    -- expiration "faute du client, réservation confirmée non retirée à temps"
    -- (colonne renseignée) — sans avoir besoin d'un statut supplémentaire.
    ADD COLUMN IF NOT EXISTS date_decision_pharmacien TIMESTAMP NULL,

    -- Raison courte, optionnelle, donnée par le pharmacien en cas de rejet.
    ADD COLUMN IF NOT EXISTS motif_rejet VARCHAR(500) NULL,

    -- Combien des 3 relances (H+10h, H+11h, H+11h45) ont déjà été envoyées
    -- au pharmacien pour cette demande — évite de renvoyer deux fois la même
    -- relance si le cron tourne plusieurs fois (idempotence).
    ADD COLUMN IF NOT EXISTS nb_relances_envoyees SMALLINT NOT NULL DEFAULT 0,

    -- Date limite pour que le pharmacien réponde à la DEMANDE (12h après la
    -- création). Différent de expire_at, qui reste la date limite de RETRAIT
    -- en pharmacie une fois la réservation confirmée.
    ADD COLUMN IF NOT EXISTS expire_demande_at TIMESTAMP NULL,

    -- Identifiant technique qui relie plusieurs lignes (produits différents)
    -- d'une même demande, tant qu'il n'y a pas encore de code_reservation
    -- (qui n'existe qu'à la confirmation). Sert à afficher/traiter groupé.
    ADD COLUMN IF NOT EXISTS groupe_demande VARCHAR(32) NULL;

CREATE INDEX IF NOT EXISTS idx_reservations_groupe_demande
    ON reservations (groupe_demande);

-- expire_at devient nullable : tant qu'une demande n'est pas confirmée, il
-- n'y a pas encore de délai de retrait (il ne démarre qu'à la confirmation).
ALTER TABLE reservations
    ALTER COLUMN expire_at DROP NOT NULL;

-- code_reservation devient nullable : le code n'existe qu'une fois la
-- réservation confirmée par le pharmacien, plus dès la demande.
ALTER TABLE reservations
    ALTER COLUMN code_reservation DROP NOT NULL;


-- ----- 2. Garde-fou de cohérence -----
-- nb_relances_envoyees ne doit jamais dépasser 3 (les 3 relances prévues).
ALTER TABLE reservations
    DROP CONSTRAINT IF EXISTS chk_reservations_nb_relances;
ALTER TABLE reservations
    ADD CONSTRAINT chk_reservations_nb_relances
    CHECK (nb_relances_envoyees BETWEEN 0 AND 3);


-- ----- 3. Index -----
-- Le cron de relance et le cron d'expiration vont scanner régulièrement les
-- demandes en attente : un index dédié évite de parcourir toute la table.
CREATE INDEX IF NOT EXISTS idx_reservations_demandes_en_cours
    ON reservations (statut, expire_demande_at)
    WHERE statut = 'demandee';

COMMIT;

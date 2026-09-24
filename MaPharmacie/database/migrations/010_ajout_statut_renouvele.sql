-- ============================================================================
-- Migration 010 — Ajout de la valeur d'enum manquante 'renouvele'
-- ----------------------------------------------------------------------------
-- Bug réel trouvé en comparant le schéma exporté (pg_dump) avec le code :
-- int_Client/Dos-php/renouveler_reservation.php écrit
--   "SET statut = 'renouvele'"
-- mais cette valeur n'a jamais été ajoutée au type reservation_status.
-- La migration 005 mentionnait 'renouvele' dans son commentaire décrivant le
-- cycle de vie, mais n'a en réalité ajouté que 'demandee' et 'rejetee' — le
-- renouvellement plante donc dès qu'un client renouvelle réellement une
-- demande expirée.
--
-- IMPORTANT : comme pour la migration 005, une valeur d'enum ajoutée ne peut
-- pas être utilisée dans la même transaction que celle qui l'a créée. Cette
-- instruction doit donc être exécutée seule, jamais entourée d'un
-- BEGIN/COMMIT avec d'autres instructions qui utiliseraient 'renouvele'.
-- ============================================================================

ALTER TYPE reservation_status ADD VALUE IF NOT EXISTS 'renouvele';

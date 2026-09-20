-- ============================================================================
-- Migration 006 — Comptes pharmacien (mini-admin)
-- ----------------------------------------------------------------------------
-- Conforme à int_Admin_specification_fonctionnelle.md §4.4 : table "roles"
-- séparée (pas un ENUM) pour pouvoir ajouter un futur rôle sans migration
-- lourde, et "administrateurs", distincte de "users" (les clients).
--
-- Portée de cette migration : uniquement ce qu'il faut pour qu'un pharmacien
-- puisse se connecter et traiter les demandes de réservation de SA pharmacie.
-- Rien sur demandes_modification, ni sur un futur rôle "administration
-- plateforme" — ce sera une migration séparée le moment venu.
-- ============================================================================

BEGIN;

-- ----- 1. Table des rôles -----

CREATE TABLE IF NOT EXISTS roles (
    id_role     SERIAL PRIMARY KEY,
    nom_role    VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255)
);

INSERT INTO roles (nom_role, description)
VALUES ('pharmacien', 'Gère sa propre pharmacie : demandes de réservation, produits, etc.')
ON CONFLICT (nom_role) DO NOTHING;


-- ----- 2. Table des comptes administrateurs (pharmaciens pour l'instant) -----

CREATE TABLE IF NOT EXISTS administrateurs (
    id_administrateur SERIAL PRIMARY KEY,

    -- NULL réservé au futur rôle "administration plateforme" (pas utilisé ici) —
    -- pour un pharmacien, toujours renseigné : ON DELETE RESTRICT, jamais de
    -- suppression physique d'une pharmacie qui aurait encore des comptes actifs.
    id_pharmacie INTEGER REFERENCES pharmacies(id_pharmacie) ON DELETE RESTRICT,

    id_role  INTEGER NOT NULL REFERENCES roles(id_role) ON DELETE RESTRICT,

    nom      VARCHAR(100) NOT NULL,
    prenom   VARCHAR(100) NOT NULL,
    email    VARCHAR(255) NOT NULL UNIQUE,

    mot_de_passe_hash VARCHAR(255) NOT NULL,

    -- Soft-delete uniquement, jamais de suppression physique d'un compte
    -- (traçabilité de l'audit, cf. charte de rigueur).
    statut VARCHAR(20) NOT NULL DEFAULT 'actif',

    -- Même logique que users.info_modif_mdp : invalide les sessions ouvertes
    -- ailleurs si le mot de passe change.
    date_modif_mdp TIMESTAMP NOT NULL DEFAULT NOW(),

    date_creation TIMESTAMP NOT NULL DEFAULT NOW()
);

ALTER TABLE administrateurs
    DROP CONSTRAINT IF EXISTS chk_administrateurs_statut;
ALTER TABLE administrateurs
    ADD CONSTRAINT chk_administrateurs_statut
    CHECK (statut IN ('actif', 'desactive'));

-- Email unique déjà garanti par la contrainte UNIQUE ci-dessus (comportement
-- attendu : c'est aussi l'identifiant de connexion).

CREATE INDEX IF NOT EXISTS idx_administrateurs_pharmacie
    ON administrateurs (id_pharmacie);


-- ----- 3. Protection anti-brute-force dédiée aux comptes admin -----
-- Table séparée de tentatives_connexion (réservée aux clients) : même
-- principe, mais on ne veut jamais qu'un déluge de tentatives sur des
-- comptes clients puisse, par erreur de requête partagée, affecter le
-- comptage des comptes pharmacien (et inversement).

CREATE TABLE IF NOT EXISTS tentatives_connexion_admin (
    id_tentative   SERIAL PRIMARY KEY,
    identifiant    VARCHAR(255) NOT NULL,
    adresse_ip     VARCHAR(45)  NOT NULL,
    reussie        BOOLEAN NOT NULL,
    date_tentative TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_tentatives_admin_identifiant_date
    ON tentatives_connexion_admin (identifiant, date_tentative);

CREATE INDEX IF NOT EXISTS idx_tentatives_admin_ip_date
    ON tentatives_connexion_admin (adresse_ip, date_tentative);

COMMIT;

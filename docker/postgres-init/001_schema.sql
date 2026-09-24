--
-- PostgreSQL database dump
--

\restrict VG2eidY8bhWfTFfa0fbQFfBAaKnL3J2UW1tMF1st8cDAiFUDL7daa7grha0wXRc

-- Dumped from database version 17.11
-- Dumped by pg_dump version 17.11

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: pg_trgm; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA public;


--
-- Name: EXTENSION pg_trgm; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pg_trgm IS 'text similarity measurement and index searching based on trigrams';


--
-- Name: reservation_status; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.reservation_status AS ENUM (
    'en_attente',
    'confirmee',
    'annulee',
    'expiree',
    'demandee',
    'rejetee',
    'renouvele'
);


--
-- Name: type_garde_tri; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.type_garde_tri AS ENUM (
    'officine',
    'urgence'
);


--
-- Name: user_role; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.user_role AS ENUM (
    'client',
    'pharmacien',
    'admin'
);


--
-- Name: zone_type; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.zone_type AS ENUM (
    'region',
    'prefecture',
    'commune',
    'quartier'
);


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: administrateurs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.administrateurs (
    id_administrateur integer NOT NULL,
    id_pharmacie integer,
    id_role integer NOT NULL,
    nom character varying(100) NOT NULL,
    prenom character varying(100) NOT NULL,
    email character varying(255) NOT NULL,
    mot_de_passe_hash character varying(255) NOT NULL,
    statut character varying(20) DEFAULT 'actif'::character varying NOT NULL,
    date_modif_mdp timestamp without time zone DEFAULT now() NOT NULL,
    date_creation timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT chk_administrateurs_statut CHECK (((statut)::text = ANY ((ARRAY['actif'::character varying, 'desactive'::character varying])::text[])))
);


--
-- Name: administrateurs_id_administrateur_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.administrateurs_id_administrateur_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: administrateurs_id_administrateur_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.administrateurs_id_administrateur_seq OWNED BY public.administrateurs.id_administrateur;


--
-- Name: assurances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.assurances (
    id_assurance integer NOT NULL,
    nom_assurance character varying(50) NOT NULL,
    logo_assurance character varying(100) NOT NULL,
    date_creation timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: assurances_id_assurance_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.assurances_id_assurance_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: assurances_id_assurance_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.assurances_id_assurance_seq OWNED BY public.assurances.id_assurance;


--
-- Name: avis; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.avis (
    id_avis integer NOT NULL,
    id_user integer,
    id_pharmacie integer,
    note smallint NOT NULL,
    commentaire text,
    date_avis timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT avis_note_check CHECK (((note >= 1) AND (note <= 5)))
);


--
-- Name: avis_id_avis_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.avis_id_avis_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: avis_id_avis_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.avis_id_avis_seq OWNED BY public.avis.id_avis;


--
-- Name: calendrier_garde; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.calendrier_garde (
    id_garde integer NOT NULL,
    id_pharmacie integer NOT NULL,
    date_debut timestamp without time zone NOT NULL,
    date_fin timestamp without time zone NOT NULL,
    type_g public.type_garde_tri DEFAULT 'officine'::public.type_garde_tri,
    CONSTRAINT check_dates CHECK ((date_fin > date_debut))
);


--
-- Name: calendrier_garde_id_garde_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.calendrier_garde_id_garde_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: calendrier_garde_id_garde_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.calendrier_garde_id_garde_seq OWNED BY public.calendrier_garde.id_garde;


--
-- Name: contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contacts (
    id_contact integer NOT NULL,
    id_user integer,
    nom character varying(100) NOT NULL,
    phone_email character varying(150) NOT NULL,
    sujet character varying(200) NOT NULL,
    message text NOT NULL,
    reponse text,
    date_envoi timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    date_reponse timestamp without time zone,
    lu boolean DEFAULT false,
    plateforme character varying(20) DEFAULT 'web'::character varying
);


--
-- Name: contacts_id_contact_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contacts_id_contact_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contacts_id_contact_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contacts_id_contact_seq OWNED BY public.contacts.id_contact;


--
-- Name: historique_recherche; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.historique_recherche (
    id_historique integer NOT NULL,
    id_user integer NOT NULL,
    id_produit integer NOT NULL,
    date_consultee timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: historique_recherche_id_historique_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.historique_recherche_id_historique_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: historique_recherche_id_historique_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.historique_recherche_id_historique_seq OWNED BY public.historique_recherche.id_historique;


--
-- Name: journal_securite_compte; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.journal_securite_compte (
    id_evenement integer NOT NULL,
    id_user integer NOT NULL,
    type_evenement character varying(50) NOT NULL,
    id_token integer,
    adresse_ip character varying(45),
    identifiant_appareil character varying(255),
    plateforme character varying(20),
    date_evenement timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: journal_securite_compte_id_evenement_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.journal_securite_compte_id_evenement_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: journal_securite_compte_id_evenement_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.journal_securite_compte_id_evenement_seq OWNED BY public.journal_securite_compte.id_evenement;


--
-- Name: localites; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.localites (
    id_localite integer NOT NULL,
    nom_localite character varying(100) NOT NULL,
    type_zone public.zone_type NOT NULL,
    parent_id integer
);


--
-- Name: localites_id_localite_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.localites_id_localite_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: localites_id_localite_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.localites_id_localite_seq OWNED BY public.localites.id_localite;


--
-- Name: pharmacie_assurances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pharmacie_assurances (
    id_pharmacie_assurance integer NOT NULL,
    id_pharmacie integer NOT NULL,
    id_assurance integer NOT NULL,
    date_ajout timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: pharmacie_assurances_id_pharmacie_assurance_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pharmacie_assurances_id_pharmacie_assurance_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pharmacie_assurances_id_pharmacie_assurance_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pharmacie_assurances_id_pharmacie_assurance_seq OWNED BY public.pharmacie_assurances.id_pharmacie_assurance;


--
-- Name: pharmacies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pharmacies (
    id_pharmacie integer NOT NULL,
    nom_pharmacie character varying(150) NOT NULL,
    adresse text NOT NULL,
    ville character varying(100) DEFAULT 'Lomé'::character varying,
    commune character varying(100),
    quartier character varying(100),
    latitude numeric(10,8),
    longitude numeric(11,8),
    statut_garde boolean DEFAULT false,
    heure_ouverture time without time zone,
    heure_fermeture time without time zone,
    telephone_pharmacie character varying(20),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    image_url text,
    statut character varying(20) DEFAULT 'active'::character varying NOT NULL,
    CONSTRAINT chk_pharmacies_statut CHECK (((statut)::text = ANY ((ARRAY['active'::character varying, 'desactivee'::character varying])::text[])))
);


--
-- Name: pharmacies_id_pharmacie_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pharmacies_id_pharmacie_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pharmacies_id_pharmacie_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pharmacies_id_pharmacie_seq OWNED BY public.pharmacies.id_pharmacie;


--
-- Name: produits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.produits (
    id_produit integer NOT NULL,
    nom_medicament character varying(150) NOT NULL,
    nom_generique character varying(150),
    forme_pharmaceutique character varying(100) NOT NULL,
    dosage character varying(50),
    conditionnement character varying(100),
    description text,
    prix_unitaire_fcfa integer NOT NULL,
    image_url text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: produits_id_produit_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.produits_id_produit_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: produits_id_produit_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.produits_id_produit_seq OWNED BY public.produits.id_produit;


--
-- Name: reservations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reservations (
    id_res integer NOT NULL,
    id_user integer NOT NULL,
    id_stock integer NOT NULL,
    quantite_reservee integer DEFAULT 1 NOT NULL,
    code_reservation character varying(10),
    date_reservation timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    expire_at timestamp without time zone,
    statut public.reservation_status DEFAULT 'en_attente'::public.reservation_status,
    reserve_code_renouvele character varying(20),
    date_decision_pharmacien timestamp without time zone,
    motif_rejet character varying(500),
    nb_relances_envoyees smallint DEFAULT 0 NOT NULL,
    expire_demande_at timestamp without time zone,
    groupe_demande character varying(32),
    renouvelable boolean DEFAULT true NOT NULL,
    nb_rappels_retrait_envoyes smallint DEFAULT 0 NOT NULL,
    CONSTRAINT chk_reservations_nb_rappels_retrait CHECK (((nb_rappels_retrait_envoyes >= 0) AND (nb_rappels_retrait_envoyes <= 2))),
    CONSTRAINT chk_reservations_nb_relances CHECK (((nb_relances_envoyees >= 0) AND (nb_relances_envoyees <= 3)))
);


--
-- Name: reservations_id_res_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reservations_id_res_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reservations_id_res_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reservations_id_res_seq OWNED BY public.reservations.id_res;


--
-- Name: reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reset_tokens (
    id_token integer NOT NULL,
    id_user integer NOT NULL,
    token character varying(255) NOT NULL,
    date_expiration timestamp without time zone NOT NULL,
    utilise boolean DEFAULT false,
    date_creation timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    canal character varying(10) DEFAULT 'email'::character varying NOT NULL,
    CONSTRAINT chk_reset_tokens_canal CHECK (((canal)::text = ANY ((ARRAY['email'::character varying, 'sms'::character varying])::text[])))
);


--
-- Name: reset_tokens_id_token_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reset_tokens_id_token_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reset_tokens_id_token_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reset_tokens_id_token_seq OWNED BY public.reset_tokens.id_token;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id_role integer NOT NULL,
    nom_role character varying(50) NOT NULL,
    description character varying(255)
);


--
-- Name: roles_id_role_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_role_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_role_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_role_seq OWNED BY public.roles.id_role;


--
-- Name: statistiques_produits_populaires; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.statistiques_produits_populaires (
    id_produit integer NOT NULL,
    nb_reservations integer DEFAULT 0 NOT NULL,
    calcule_le timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: stocks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stocks (
    id_stock integer NOT NULL,
    id_pharmacie integer NOT NULL,
    id_produit integer NOT NULL,
    quantite_disponible integer DEFAULT 0,
    seuil_alerte integer DEFAULT 5,
    derniere_mise_a_jour timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: stocks_id_stock_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stocks_id_stock_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stocks_id_stock_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stocks_id_stock_seq OWNED BY public.stocks.id_stock;


--
-- Name: tentatives_connexion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tentatives_connexion (
    id_tentative integer NOT NULL,
    identifiant character varying(255) NOT NULL,
    adresse_ip character varying(45) NOT NULL,
    reussie boolean DEFAULT false NOT NULL,
    date_tentative timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: tentatives_connexion_admin; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tentatives_connexion_admin (
    id_tentative integer NOT NULL,
    identifiant character varying(255) NOT NULL,
    adresse_ip character varying(45) NOT NULL,
    reussie boolean NOT NULL,
    date_tentative timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: tentatives_connexion_admin_id_tentative_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tentatives_connexion_admin_id_tentative_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tentatives_connexion_admin_id_tentative_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tentatives_connexion_admin_id_tentative_seq OWNED BY public.tentatives_connexion_admin.id_tentative;


--
-- Name: tentatives_connexion_id_tentative_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tentatives_connexion_id_tentative_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tentatives_connexion_id_tentative_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tentatives_connexion_id_tentative_seq OWNED BY public.tentatives_connexion.id_tentative;


--
-- Name: tentatives_malveillantes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tentatives_malveillantes (
    id_malveillante integer NOT NULL,
    identifiant character varying(255),
    adresse_ip character varying(45),
    raison character varying(255) NOT NULL,
    date_detection timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: tentatives_malveillantes_id_malveillante_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tentatives_malveillantes_id_malveillante_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tentatives_malveillantes_id_malveillante_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tentatives_malveillantes_id_malveillante_seq OWNED BY public.tentatives_malveillantes.id_malveillante;


--
-- Name: tentatives_reset_mdp; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tentatives_reset_mdp (
    id_tentative integer NOT NULL,
    identifiant character varying(255) NOT NULL,
    adresse_ip character varying(45) NOT NULL,
    bloquee boolean DEFAULT false,
    date_tentative timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: tentatives_reset_mdp_id_tentative_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tentatives_reset_mdp_id_tentative_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tentatives_reset_mdp_id_tentative_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tentatives_reset_mdp_id_tentative_seq OWNED BY public.tentatives_reset_mdp.id_tentative;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id_user integer NOT NULL,
    nom character varying(100) NOT NULL,
    prenom character varying(100) NOT NULL,
    phone_email character varying(150) NOT NULL,
    password character varying(255) NOT NULL,
    role public.user_role DEFAULT 'client'::public.user_role,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    info_modif_mdp timestamp without time zone,
    assurance_defaut character varying(50),
    email_recuperation character varying(255),
    telephone_recuperation character varying(20),
    CONSTRAINT chk_email_recuperation_format CHECK (((email_recuperation IS NULL) OR ((email_recuperation)::text ~* '^[^@\s]+@[^@\s]+\.[^@\s]+$'::text))),
    CONSTRAINT chk_telephone_recuperation_format CHECK (((telephone_recuperation IS NULL) OR ((telephone_recuperation)::text ~ '^[0-9+\s]{8,20}$'::text)))
);


--
-- Name: users_id_user_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_user_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_user_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_user_seq OWNED BY public.users.id_user;


--
-- Name: administrateurs id_administrateur; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.administrateurs ALTER COLUMN id_administrateur SET DEFAULT nextval('public.administrateurs_id_administrateur_seq'::regclass);


--
-- Name: assurances id_assurance; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assurances ALTER COLUMN id_assurance SET DEFAULT nextval('public.assurances_id_assurance_seq'::regclass);


--
-- Name: avis id_avis; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.avis ALTER COLUMN id_avis SET DEFAULT nextval('public.avis_id_avis_seq'::regclass);


--
-- Name: calendrier_garde id_garde; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calendrier_garde ALTER COLUMN id_garde SET DEFAULT nextval('public.calendrier_garde_id_garde_seq'::regclass);


--
-- Name: contacts id_contact; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts ALTER COLUMN id_contact SET DEFAULT nextval('public.contacts_id_contact_seq'::regclass);


--
-- Name: historique_recherche id_historique; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historique_recherche ALTER COLUMN id_historique SET DEFAULT nextval('public.historique_recherche_id_historique_seq'::regclass);


--
-- Name: journal_securite_compte id_evenement; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.journal_securite_compte ALTER COLUMN id_evenement SET DEFAULT nextval('public.journal_securite_compte_id_evenement_seq'::regclass);


--
-- Name: localites id_localite; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.localites ALTER COLUMN id_localite SET DEFAULT nextval('public.localites_id_localite_seq'::regclass);


--
-- Name: pharmacie_assurances id_pharmacie_assurance; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pharmacie_assurances ALTER COLUMN id_pharmacie_assurance SET DEFAULT nextval('public.pharmacie_assurances_id_pharmacie_assurance_seq'::regclass);


--
-- Name: pharmacies id_pharmacie; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pharmacies ALTER COLUMN id_pharmacie SET DEFAULT nextval('public.pharmacies_id_pharmacie_seq'::regclass);


--
-- Name: produits id_produit; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.produits ALTER COLUMN id_produit SET DEFAULT nextval('public.produits_id_produit_seq'::regclass);


--
-- Name: reservations id_res; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations ALTER COLUMN id_res SET DEFAULT nextval('public.reservations_id_res_seq'::regclass);


--
-- Name: reset_tokens id_token; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reset_tokens ALTER COLUMN id_token SET DEFAULT nextval('public.reset_tokens_id_token_seq'::regclass);


--
-- Name: roles id_role; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id_role SET DEFAULT nextval('public.roles_id_role_seq'::regclass);


--
-- Name: stocks id_stock; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stocks ALTER COLUMN id_stock SET DEFAULT nextval('public.stocks_id_stock_seq'::regclass);


--
-- Name: tentatives_connexion id_tentative; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tentatives_connexion ALTER COLUMN id_tentative SET DEFAULT nextval('public.tentatives_connexion_id_tentative_seq'::regclass);


--
-- Name: tentatives_connexion_admin id_tentative; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tentatives_connexion_admin ALTER COLUMN id_tentative SET DEFAULT nextval('public.tentatives_connexion_admin_id_tentative_seq'::regclass);


--
-- Name: tentatives_malveillantes id_malveillante; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tentatives_malveillantes ALTER COLUMN id_malveillante SET DEFAULT nextval('public.tentatives_malveillantes_id_malveillante_seq'::regclass);


--
-- Name: tentatives_reset_mdp id_tentative; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tentatives_reset_mdp ALTER COLUMN id_tentative SET DEFAULT nextval('public.tentatives_reset_mdp_id_tentative_seq'::regclass);


--
-- Name: users id_user; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id_user SET DEFAULT nextval('public.users_id_user_seq'::regclass);


--
-- Name: administrateurs administrateurs_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.administrateurs
    ADD CONSTRAINT administrateurs_email_key UNIQUE (email);


--
-- Name: administrateurs administrateurs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.administrateurs
    ADD CONSTRAINT administrateurs_pkey PRIMARY KEY (id_administrateur);


--
-- Name: assurances assurances_nom_assurance_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assurances
    ADD CONSTRAINT assurances_nom_assurance_key UNIQUE (nom_assurance);


--
-- Name: assurances assurances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assurances
    ADD CONSTRAINT assurances_pkey PRIMARY KEY (id_assurance);


--
-- Name: avis avis_id_user_id_pharmacie_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.avis
    ADD CONSTRAINT avis_id_user_id_pharmacie_key UNIQUE (id_user, id_pharmacie);


--
-- Name: avis avis_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.avis
    ADD CONSTRAINT avis_pkey PRIMARY KEY (id_avis);


--
-- Name: calendrier_garde calendrier_garde_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calendrier_garde
    ADD CONSTRAINT calendrier_garde_pkey PRIMARY KEY (id_garde);


--
-- Name: contacts contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_pkey PRIMARY KEY (id_contact);


--
-- Name: pharmacie_assurances contrainte_unicite_pharmacie_assurance; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pharmacie_assurances
    ADD CONSTRAINT contrainte_unicite_pharmacie_assurance UNIQUE (id_pharmacie, id_assurance);


--
-- Name: historique_recherche historique_recherche_id_user_id_produit_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historique_recherche
    ADD CONSTRAINT historique_recherche_id_user_id_produit_key UNIQUE (id_user, id_produit);


--
-- Name: historique_recherche historique_recherche_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historique_recherche
    ADD CONSTRAINT historique_recherche_pkey PRIMARY KEY (id_historique);


--
-- Name: journal_securite_compte journal_securite_compte_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.journal_securite_compte
    ADD CONSTRAINT journal_securite_compte_pkey PRIMARY KEY (id_evenement);


--
-- Name: localites localites_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.localites
    ADD CONSTRAINT localites_pkey PRIMARY KEY (id_localite);


--
-- Name: pharmacie_assurances pharmacie_assurances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pharmacie_assurances
    ADD CONSTRAINT pharmacie_assurances_pkey PRIMARY KEY (id_pharmacie_assurance);


--
-- Name: pharmacies pharmacies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pharmacies
    ADD CONSTRAINT pharmacies_pkey PRIMARY KEY (id_pharmacie);


--
-- Name: produits produits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.produits
    ADD CONSTRAINT produits_pkey PRIMARY KEY (id_produit);


--
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (id_res);


--
-- Name: reset_tokens reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reset_tokens
    ADD CONSTRAINT reset_tokens_pkey PRIMARY KEY (id_token);


--
-- Name: roles roles_nom_role_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nom_role_key UNIQUE (nom_role);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id_role);


--
-- Name: statistiques_produits_populaires statistiques_produits_populaires_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.statistiques_produits_populaires
    ADD CONSTRAINT statistiques_produits_populaires_pkey PRIMARY KEY (id_produit);


--
-- Name: stocks stocks_id_pharmacie_id_produit_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stocks
    ADD CONSTRAINT stocks_id_pharmacie_id_produit_key UNIQUE (id_pharmacie, id_produit);


--
-- Name: stocks stocks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stocks
    ADD CONSTRAINT stocks_pkey PRIMARY KEY (id_stock);


--
-- Name: tentatives_connexion_admin tentatives_connexion_admin_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tentatives_connexion_admin
    ADD CONSTRAINT tentatives_connexion_admin_pkey PRIMARY KEY (id_tentative);


--
-- Name: tentatives_connexion tentatives_connexion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tentatives_connexion
    ADD CONSTRAINT tentatives_connexion_pkey PRIMARY KEY (id_tentative);


--
-- Name: tentatives_malveillantes tentatives_malveillantes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tentatives_malveillantes
    ADD CONSTRAINT tentatives_malveillantes_pkey PRIMARY KEY (id_malveillante);


--
-- Name: tentatives_reset_mdp tentatives_reset_mdp_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tentatives_reset_mdp
    ADD CONSTRAINT tentatives_reset_mdp_pkey PRIMARY KEY (id_tentative);


--
-- Name: users users_phone_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_phone_email_key UNIQUE (phone_email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id_user);


--
-- Name: idx_administrateurs_pharmacie; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_administrateurs_pharmacie ON public.administrateurs USING btree (id_pharmacie);


--
-- Name: idx_pharmacie_assurances_id_assurance; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pharmacie_assurances_id_assurance ON public.pharmacie_assurances USING btree (id_assurance);


--
-- Name: idx_pharmacie_assurances_id_pharmacie; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pharmacie_assurances_id_pharmacie ON public.pharmacie_assurances USING btree (id_pharmacie);


--
-- Name: idx_pharmacies_latitude; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pharmacies_latitude ON public.pharmacies USING btree (latitude);


--
-- Name: idx_pharmacies_longitude; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pharmacies_longitude ON public.pharmacies USING btree (longitude);


--
-- Name: idx_pharmacies_ville; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pharmacies_ville ON public.pharmacies USING btree (ville);


--
-- Name: idx_produits_nom_medicament_trgm; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_produits_nom_medicament_trgm ON public.produits USING gin (nom_medicament public.gin_trgm_ops);


--
-- Name: idx_reservations_demandes_en_cours; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_reservations_demandes_en_cours ON public.reservations USING btree (statut, expire_demande_at) WHERE (statut = 'demandee'::public.reservation_status);


--
-- Name: idx_reservations_groupe_demande; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_reservations_groupe_demande ON public.reservations USING btree (groupe_demande);


--
-- Name: idx_reservations_id_stock; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_reservations_id_stock ON public.reservations USING btree (id_stock);


--
-- Name: idx_reservations_rappel_retrait; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_reservations_rappel_retrait ON public.reservations USING btree (statut, expire_at, nb_rappels_retrait_envoyes) WHERE ((statut = 'confirmee'::public.reservation_status) AND (nb_rappels_retrait_envoyes < 2));


--
-- Name: idx_statistiques_produits_populaires_nb; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_statistiques_produits_populaires_nb ON public.statistiques_produits_populaires USING btree (nb_reservations DESC);


--
-- Name: idx_stocks_id_pharmacie; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stocks_id_pharmacie ON public.stocks USING btree (id_pharmacie);


--
-- Name: idx_stocks_id_produit; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_stocks_id_produit ON public.stocks USING btree (id_produit);


--
-- Name: idx_tentatives_admin_identifiant_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tentatives_admin_identifiant_date ON public.tentatives_connexion_admin USING btree (identifiant, date_tentative);


--
-- Name: idx_tentatives_admin_ip_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tentatives_admin_ip_date ON public.tentatives_connexion_admin USING btree (adresse_ip, date_tentative);


--
-- Name: idx_tentatives_connexion_identifiant; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tentatives_connexion_identifiant ON public.tentatives_connexion USING btree (identifiant, date_tentative);


--
-- Name: idx_tentatives_connexion_ip; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_tentatives_connexion_ip ON public.tentatives_connexion USING btree (adresse_ip, date_tentative);


--
-- Name: administrateurs administrateurs_id_pharmacie_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.administrateurs
    ADD CONSTRAINT administrateurs_id_pharmacie_fkey FOREIGN KEY (id_pharmacie) REFERENCES public.pharmacies(id_pharmacie) ON DELETE RESTRICT;


--
-- Name: administrateurs administrateurs_id_role_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.administrateurs
    ADD CONSTRAINT administrateurs_id_role_fkey FOREIGN KEY (id_role) REFERENCES public.roles(id_role) ON DELETE RESTRICT;


--
-- Name: avis avis_id_pharmacie_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.avis
    ADD CONSTRAINT avis_id_pharmacie_fkey FOREIGN KEY (id_pharmacie) REFERENCES public.pharmacies(id_pharmacie) ON DELETE CASCADE;


--
-- Name: avis avis_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.avis
    ADD CONSTRAINT avis_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON DELETE SET NULL;


--
-- Name: contacts contacts_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contacts
    ADD CONSTRAINT contacts_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON DELETE SET NULL;


--
-- Name: localites fk_parent; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.localites
    ADD CONSTRAINT fk_parent FOREIGN KEY (parent_id) REFERENCES public.localites(id_localite) ON DELETE CASCADE;


--
-- Name: stocks fk_pharmacie; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stocks
    ADD CONSTRAINT fk_pharmacie FOREIGN KEY (id_pharmacie) REFERENCES public.pharmacies(id_pharmacie) ON DELETE CASCADE;


--
-- Name: calendrier_garde fk_pharmacie_garde; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calendrier_garde
    ADD CONSTRAINT fk_pharmacie_garde FOREIGN KEY (id_pharmacie) REFERENCES public.pharmacies(id_pharmacie) ON DELETE CASCADE;


--
-- Name: stocks fk_produit; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stocks
    ADD CONSTRAINT fk_produit FOREIGN KEY (id_produit) REFERENCES public.produits(id_produit) ON DELETE CASCADE;


--
-- Name: reservations fk_stock; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT fk_stock FOREIGN KEY (id_stock) REFERENCES public.stocks(id_stock) ON DELETE CASCADE;


--
-- Name: reservations fk_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT fk_user FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON DELETE CASCADE;


--
-- Name: historique_recherche historique_recherche_id_produit_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historique_recherche
    ADD CONSTRAINT historique_recherche_id_produit_fkey FOREIGN KEY (id_produit) REFERENCES public.produits(id_produit) ON DELETE CASCADE;


--
-- Name: historique_recherche historique_recherche_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historique_recherche
    ADD CONSTRAINT historique_recherche_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON DELETE CASCADE;


--
-- Name: journal_securite_compte journal_securite_compte_id_token_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.journal_securite_compte
    ADD CONSTRAINT journal_securite_compte_id_token_fkey FOREIGN KEY (id_token) REFERENCES public.reset_tokens(id_token);


--
-- Name: journal_securite_compte journal_securite_compte_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.journal_securite_compte
    ADD CONSTRAINT journal_securite_compte_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON DELETE CASCADE;


--
-- Name: pharmacie_assurances pharmacie_assurances_id_assurance_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pharmacie_assurances
    ADD CONSTRAINT pharmacie_assurances_id_assurance_fkey FOREIGN KEY (id_assurance) REFERENCES public.assurances(id_assurance) ON DELETE CASCADE;


--
-- Name: pharmacie_assurances pharmacie_assurances_id_pharmacie_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pharmacie_assurances
    ADD CONSTRAINT pharmacie_assurances_id_pharmacie_fkey FOREIGN KEY (id_pharmacie) REFERENCES public.pharmacies(id_pharmacie) ON DELETE CASCADE;


--
-- Name: reset_tokens reset_tokens_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reset_tokens
    ADD CONSTRAINT reset_tokens_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.users(id_user);


--
-- Name: statistiques_produits_populaires statistiques_produits_populaires_id_produit_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.statistiques_produits_populaires
    ADD CONSTRAINT statistiques_produits_populaires_id_produit_fkey FOREIGN KEY (id_produit) REFERENCES public.produits(id_produit) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict VG2eidY8bhWfTFfa0fbQFfBAaKnL3J2UW1tMF1st8cDAiFUDL7daa7grha0wXRc


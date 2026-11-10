-- Adminer 4.17.1 PostgreSQL 17.4 (Debian 17.4-1.pgdg120+2) dump

DROP TABLE IF EXISTS "patient";
CREATE TABLE "public"."patient" (
    "id" character varying(64) NOT NULL,
    "nom" character varying(64) NOT NULL,
    "prenom" character varying(64) NOT NULL,
    "date_naissance" date,
    "adresse" text,
    "code_postal" character varying(8),
    "ville" character varying(64),
    "email" character varying(128),
    "telephone" character varying(24) NOT NULL
) WITH (oids = false);


DROP TABLE IF EXISTS "praticien";
CREATE TABLE "public"."praticien" (
    "id" character varying(64) NOT NULL,
    "nom" character varying(48) NOT NULL,
    "prenom" character varying(48) NOT NULL,
    "ville" character varying(48) NOT NULL,
    "email" character varying(128) NOT NULL,
    "telephone" character varying(24) NOT NULL,
    "specialite_id" integer NOT NULL,
    "structure_id" uuid,
    "rpps_id" character varying(12),
    "organisation" bit(1) DEFAULT '0' NOT NULL,
    "nouveau_patient" bit(1) DEFAULT '1' NOT NULL,
    "titre" character varying(8) DEFAULT 'Dr.' NOT NULL
) WITH (oids = false);


DROP TABLE IF EXISTS "rdv";
CREATE TABLE "public"."rdv" (
    "id" character varying(64) NOT NULL,
    "praticien_id" character varying(64) NOT NULL,
    "patient_id" character varying(64) NOT NULL,
    "date_heure_debut" timestamp NOT NULL,
    "status" smallint DEFAULT '0' NOT NULL,
    "duree" smallint DEFAULT '30' NOT NULL,
    "date_heure_fin" timestamp,
    "date_creation" timestamp,
    "motif_visite" character varying(128)
) WITH (oids = false);


DROP TABLE IF EXISTS "specialite";
DROP SEQUENCE IF EXISTS specialite_id_seq;
CREATE SEQUENCE specialite_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."specialite" (
    "id" integer DEFAULT nextval('specialite_id_seq') NOT NULL,
    "libelle" character varying(48) NOT NULL,
    "description" text,
    CONSTRAINT "specialite_pkey" PRIMARY KEY ("id")
) WITH (oids = false);


-- 2026-09-07 07:29:46.179361+00

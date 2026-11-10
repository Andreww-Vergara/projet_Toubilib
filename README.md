# Toubilib

API REST de prise de rendez-vous médicaux — PHP 8.5, Slim 4, PHP-DI, PostgreSQL.
Architecture hexagonale (domaine / application / adaptateurs).

TD couverts : **TD1.1** (architecture hexagonale, annulation), **TD1.2** (injection de dépendances),
**TD1.3** (DTO, validation, création de RDV), **TD1.4** (API REST, HATEOAS, CORS).

---

## Démarrage

Prérequis : Docker Desktop (ou Docker Engine + Compose).

```bash
# 1. Fichiers de configuration (non versionnés : ils contiennent les mots de passe)
cp toubilib.env.dist toubilib.env
cp app/config/.env.dist app/config/.env

# 2. Lancement (le premier démarrage exécute composer install)
docker compose up -d
```

La base est créée et remplie automatiquement au premier démarrage à partir de `app/sql/*.sql`.

| Service | URL |
|---|---|
| API | http://localhost:7080 |
| Contrôle API + BD | http://localhost:7080/health |
| Adminer | http://localhost:8880 (système PostgreSQL, serveur `toubilib.db`, identifiants de `toubilib.env`) |

Réinitialiser la base (supprime les RDV créés pendant les tests) :

```bash
docker compose down && docker compose up -d
```

---

## Tests

Tests unitaires Pest (domaine, DTO, services) — sans base de données, grâce à des repositories en mémoire :

```bash
docker compose exec api.toubilib php vendor/bin/pest
```

Tests de l'API : collection **Bruno** dans `bruno/`
(Open Collection → dossier `bruno` → environnement `local` → *Run*).

---

## Routes

| # | Fonctionnalité | Méthode et URI | Succès | Erreurs |
|---|---|---|---|---|
| 1 | Lister les praticiens | `GET /praticiens` | 200 | |
| 2 | Détail d'un praticien | `GET /praticiens/{id}` | 200 | 404 |
| 3 | Détail d'un RDV | `GET /rdvs/{id}` | 200 | 404 |
| 4 | Agenda d'un praticien | `GET /praticiens/{id}/rdvs?debut=&fin=` | 200 | 404, 422 |
| 5 | Créer un RDV | `POST /rdvs` | 201 + `Location` | 400, 409, 422 |
| 6 | Annuler un RDV | `PATCH /rdvs/{id}/annuler` | 200 | 404, 409 |
| 7 | Créneaux occupés | `GET /praticiens/{id}/creneaux?debut=&fin=` | 200 | 404, 422 |
| – | Honorer / ignorer un RDV | `PATCH /rdvs/{id}/honorer` · `/ignorer` | 200 | 404, 409 |

`debut` et `fin` : `2025-12-05` (journée entière) ou `2025-12-05T08:00:00`.

Corps de `POST /rdvs` :

```json
{
  "praticien_id": "4305f5e9-be5a-4ccf-8792-7e07d7017363",
  "patient_id": "da362a38-c1bf-39ed-8b2e-3bc99a86cfde",
  "date_heure_debut": "2026-12-07 10:00",
  "motif_visite": "CO"
}
```

`motif_visite` : `CI` consultation initiale (30 min), `CO` consultation (20 min), `CS` consultation de suivi (15 min).

### Format des réponses

Ressources et collections suivent la même structure, avec liens HATEOAS :

```json
{ "type": "ressource", "rendez_vous": { "...": "..." }, "links": { "self": { "href": "/rdvs/..." } } }
```

Les liens `annuler`, `honorer` et `ignorer` ne sont présents que si le RDV est encore `PREVU`.
Les erreurs sont renvoyées en JSON : `{ "error": { "status": 409, "message": "..." } }`.

---

## Règles métier (création et changement d'état d'un RDV)

- Date dans le futur, du **lundi au vendredi**, RDV entièrement compris dans **09:00–12:00** ou **14:00–19:00**
  (plages déduites des données existantes).
- Une **consultation initiale** n'est possible que si le praticien accepte de nouveaux patients.
- Le créneau ne doit **chevaucher** aucun RDV du praticien (les RDV annulés libèrent leur créneau).
- Un RDV ne change d'état que s'il est `PREVU` : **annuler** avant qu'il commence,
  **honorer** ou **ignorer** une fois commencé.

---

## Organisation du code

```
app/
├── config/
│   ├── settings.php      valeurs de configuration (lues dans .env)
│   ├── services.php      PDO, repositories, services métier  ┐ conteneur PHP-DI
│   ├── api.php           actions et middlewares Slim          ┘ sans autowire
│   ├── routes.php
│   └── bootstrap.php
├── src/
│   ├── domain/           entités et règles métier, exceptions métier
│   ├── application/      services (cas d'utilisation), DTO, validateur, ports (interfaces)
│   └── adapters/         api/ (actions, middlewares Slim) et pdo/ (repositories PostgreSQL)
└── tests/pest/           domaine/, dto/, services/, fakes/ (repositories en mémoire)
```

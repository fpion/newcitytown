# PDR — Fonctionnalité News

**Projet** : NewCity
**Date** : 2026-04-01
**Branch** : develop
**Commit** : 6c934ae — *Event Sourcing Model*
**Statut** : Livré

---

## 1. Contexte et objectif

Implémentation d'une fonctionnalité de gestion d'articles (news) en suivant les patterns **Event Sourcing** et **CQRS** (Command Query Responsibility Segregation), dans le cadre d'une application Symfony 8.0.

L'objectif est de disposer d'un cycle de vie complet pour un article : création à l'état brouillon, puis publication.

---

## 2. Périmètre fonctionnel

| Fonctionnalité | Statut |
|---|---|
| Créer un article (titre) | Livré |
| Lister tous les articles | Livré |
| Consulter le détail d'un article | Livré |
| Publier un article brouillon | Livré |
| Empêcher la double publication | Livré |

---

## 3. Architecture

### Pattern général

```
Commande → Handler → Aggregate Root → Domain Events
                                         ↓
                                    Event Store (PostgreSQL)
                                         ↓
                                    Projector → Read Model (Vue SQL)
                                                     ↓
                                               Controller → Templates Twig
```

Le modèle d'écriture (aggregat + events) est totalement découplé du modèle de lecture (projections).

### Couches applicatives

| Couche | Rôle |
|---|---|
| **Domain** | Aggregate, Value Objects, Domain Events |
| **Application** | Commands, Handlers (orchestration) |
| **Infrastructure** | Event Store Doctrine, Projections, Read Model |
| **Controller / Templates** | UI Symfony, formulaires Twig |

---

## 4. Détail des composants

### 4.1 Domaine — `src/Domain/News/`

#### Aggregate Root : `News`

Classe centrale portant l'état et les invariants métier.

- **Constructeur** : `News::create(NewsId, title, createdAt)` — émet `NewsCreated`
- **Méthode** : `publish()` — émet `NewsPublished`, lève une `DomainException` si déjà publié
- **Application d'événements** : `applyNewsCreated()`, `applyNewsPublished()` — reconstituent l'état
- Hérite de `AggregateRoot` (Event Sourcing partagé)

#### Value Objects

| Classe | Rôle |
|---|---|
| `NewsId` | Identifiant UUID v7 typé, immuable |
| `TitleInfo` | DTO immuable exposant `title` et `createdAt` |

#### Domain Events — `src/Domain/News/Event/`

| Événement | Nom | Payload |
|---|---|---|
| `NewsCreated` | `news.created` | `title`, `created_at` (ISO 8601) |
| `NewsPublished` | `news.published` | `published_at` (ISO 8601) |

Chaque événement implémente l'interface `DomainEvent` et est sérialisable via `fromPayload()`.

#### Infrastructure partagée — `src/Domain/Shared/`

| Classe | Rôle |
|---|---|
| `AggregateRoot` | Base Event Sourcing : `recordThat()`, `apply()`, `reconstituteFrom()`, versionnage |
| `DomainEvent` | Interface contrat des événements |
| `EventStream` | Collection immuable d'événements (`IteratorAggregate`, `Countable`) |

---

### 4.2 Application — `src/Application/News/`

#### Commands

| Commande | Champs |
|---|---|
| `CreateNewsCommand` | `title: string` |
| `PublishNewsCommand` | `newsId: string` |

#### Handlers (Symfony MessageHandler)

**`CreateNewsHandler`**
1. Génère un `NewsId` (UUID v7)
2. Crée l'aggregate `News::create()`
3. Persiste les événements via `EventStoreInterface::append()`
4. Dispatche les événements vers les projecteurs via l'event bus

**`PublishNewsHandler`**
1. Charge le flux d'événements via `EventStoreInterface::load()`
2. Reconstitue l'aggregate : `News::reconstituteFrom()`
3. Appelle `publish()` sur l'aggregate
4. Persiste avec le verrou optimiste (`expectedVersion`)
5. Dispatche les nouveaux événements

---

### 4.3 Infrastructure

#### Event Store — `src/Infrastructure/EventStore/`

**Table `event_store`**

| Colonne | Type | Rôle |
|---|---|---|
| `id` | BIGSERIAL | Clé primaire |
| `aggregate_id` | UUID | Identifiant de l'agrégat |
| `event_name` | VARCHAR(255) | Nom de l'événement |
| `payload` | JSONB | Données de l'événement |
| `occurred_at` | TIMESTAMP(6) | Horodatage avec microsecondes |
| `version` | INT | Version (unique par agrégat) |

Contrainte `UNIQUE (aggregate_id, version)` — assure la cohérence du flux.
Index sur `(aggregate_id, version)` — optimise le chargement.

`DoctrineEventStore` implémente `EventStoreInterface` avec :
- Verrou optimiste (contrôle de la version courante avant `append`)
- Désérialisation des événements via mapping de classes configuré dans `services.yaml`

#### Projections — `src/Infrastructure/Projection/News/`

**`NewsListProjector`** (MessageHandler)
- `onNewsCreated()` → insert dans `news_list_view`
- `onNewsPublished()` → update `published` et `published_at`

**Table `news_list_view`**

| Colonne | Type |
|---|---|
| `id` | UUID (PK) |
| `title` | VARCHAR(255) |
| `created_at` | TIMESTAMP |
| `published` | BOOLEAN |
| `published_at` | TIMESTAMP (nullable) |

Index : `(published, published_at DESC)` — optimise les requêtes sur les articles publiés.

**`NewsListRepository`** — Lecture seule
- `findAll()` — tous les articles triés par `created_at DESC`
- `findAllPublished()` — articles publiés triés par `published_at DESC`
- `findById(id)` — article unique ou `null`

---

### 4.4 Controller et Templates

**`NewsController`** — `src/Controller/NewsController.php`

| Route | Méthode | Action |
|---|---|---|
| `GET /news` | `news_index` | Liste tous les articles |
| `GET /news/{id}` | `news_show` | Détail d'un article |
| `GET+POST /news/create` | `news_create` | Formulaire de création |
| `POST /news/{id}/publish` | `news_publish` | Publication d'un article |

**Templates Twig** — `templates/news/`

| Fichier | Rôle |
|---|---|
| `index.html.twig` | Liste avec badges Publié / Brouillon |
| `show.html.twig` | Détail avec bouton de publication conditionnel |
| `create.html.twig` | Formulaire de création avec gestion des erreurs |

---

## 5. Configuration

### `config/services.yaml`

Mapping événement ↔ classe pour la désérialisation :

```yaml
'news.created'    → App\Domain\News\Event\NewsCreated
'news.published'  → App\Domain\News\Event\NewsPublished
```

### `config/packages/messenger.yaml`

- Commands (`CreateNewsCommand`, `PublishNewsCommand`) → transport `sync`
- Events (`NewsCreated`, `NewsPublished`) → transport `sync`
- Event bus configuré avec `allow_no_handlers: true`

---

## 6. Migrations

| Migration | Objet |
|---|---|
| `Version20260330001000` | Table `event_store` |
| `Version20260330002000` | Table `news_list_view` |

---

## 7. Tests

**`tests/Domain/News/NewsTest.php`** (Pest)

| Test | Scénario |
|---|---|
| `should create a news and emit NewsCreated event` | Création et émission d'événement |
| `should publish a news and emit NewsPublished event` | Publication et émission d'événement |
| `should not publish twice` | Invariant métier double publication |
| `should reconstitute from event stream` | Reconstruction de l'agrégat |
| `should generate a unique id` | `NewsId::generate()` |
| `should reconstitute from string` | `NewsId::fromString()` |
| `should serialize and deserialize via payload` | Sérialisation `NewsCreated` |

---

## 8. Inventaire des fichiers

```
Domain (5)
├── src/Domain/News/News.php
├── src/Domain/News/NewsId.php
├── src/Domain/News/TitleInfo.php
├── src/Domain/News/Event/NewsCreated.php
└── src/Domain/News/Event/NewsPublished.php

Application (4)
├── src/Application/News/Command/CreateNewsCommand.php
├── src/Application/News/Command/PublishNewsCommand.php
├── src/Application/News/Handler/CreateNewsHandler.php
└── src/Application/News/Handler/PublishNewsHandler.php

Infrastructure (5)
├── src/Infrastructure/EventStore/EventStoreInterface.php
├── src/Infrastructure/EventStore/DoctrineEventStore.php
├── src/Infrastructure/Projection/News/NewsListProjector.php
├── src/Infrastructure/Projection/News/NewsListReadModel.php
└── src/Infrastructure/Projection/News/NewsListRepository.php

Controller & Templates (4)
├── src/Controller/NewsController.php
├── templates/news/index.html.twig
├── templates/news/show.html.twig
└── templates/news/create.html.twig

Base de données & Configuration (4)
├── migrations/Version20260330001000.php
├── migrations/Version20260330002000.php
├── config/services.yaml
└── config/packages/messenger.yaml

Tests (2)
├── tests/Domain/News/NewsTest.php
└── tests/Domain/News/NewsManagerTest.php
```

**Total : 24 fichiers**

---

## 9. Points d'attention / Axes d'évolution

| Sujet | Note |
|---|---|
| Transport `sync` | Adapté au développement. À basculer sur un transport asynchrone (AMQP/Redis) en production pour découpler les projections. |
| `NewsManagerTest.php` | Contient uniquement un placeholder. Tests d'intégration à compléter. |
| Replay de projections | Aucun mécanisme de replay n'est implémenté. À prévoir si la `news_list_view` doit être reconstruite depuis l'`event_store`. |
| Suppression / archivage | Non implémenté dans ce périmètre. |
| Pagination | Absente sur la liste des articles. |

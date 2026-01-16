# WR506D - API Films

API REST et GraphQL pour la gestion de films, acteurs, catégories et commentaires.

## Technologies

- **Framework** : Symfony 7.3
- **API** : API Platform 4.1 (REST + GraphQL)
- **Base de données** : MySQL/MariaDB (Doctrine ORM)
- **Authentification** : JWT (Lexik JWT Bundle) + API Key
- **Upload** : Vich Uploader Bundle
- **2FA** : TOTP avec Google Authenticator

## Installation

### Prérequis

- PHP >= 8.2
- Composer
- MySQL/MariaDB
- OpenSSL (pour les clés JWT)

### Étapes

```bash
# Cloner le repository
git clone https://github.com/NoheilaR/wr506d.git
cd wr506d

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos paramètres de base de données
```

### Configuration de la base de données

Dans `.env.local` :
```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/wr506d?serverVersion=8.0"
```

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# (Optionnel) Charger les fixtures
php bin/console doctrine:fixtures:load
```

### Configuration JWT

```bash
# Générer les clés JWT
php bin/console lexik:jwt:generate-keypair
```

Les clés seront créées dans `config/jwt/`.

### Lancer le serveur

```bash
symfony server:start
# ou
php -S localhost:8000 -t public
```

## Authentification

### JWT Token

**Endpoint** : `POST /auth`

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Réponse** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Utilisation** : Ajouter le header `Authorization: Bearer {token}` à chaque requête.

### API Key

**Générer une clé** : `POST /api/me/api-key` (authentifié)

**Utilisation** : Ajouter le header `X-API-KEY: {api_key}` à chaque requête.

**Autres endpoints API Key** :
- `GET /api/me/api-key` - Voir le statut de la clé
- `PATCH /api/me/api-key` - Activer/désactiver la clé
- `DELETE /api/me/api-key` - Révoquer la clé

### 2FA (Authentification à deux facteurs)

1. **Initialiser** : `POST /api/2fa/setup` - Retourne un QR code à scanner
2. **Activer** : `POST /api/2fa/enable` avec le code TOTP

Une fois activé, le login nécessite le paramètre `totp_code` :
```json
{
  "email": "user@example.com",
  "password": "password",
  "totp_code": "123456"
}
```

## Endpoints API REST

### Entités principales

| Ressource | Endpoint | Méthodes |
|-----------|----------|----------|
| Actors | `/api/actors` | GET, POST, PUT, DELETE |
| Movies | `/api/movies` | GET, POST, PUT, DELETE |
| Categories | `/api/categories` | GET, POST, PUT, DELETE |
| Comments | `/api/comments` | GET, POST, PUT, DELETE |
| Directors | `/api/directors` | GET, POST, PUT, DELETE |
| Users | `/api/users` | GET, POST, PUT, DELETE |
| Media | `/api/media_objects` | GET, POST |

### Documentation interactive

- **Swagger UI** : `/api/docs`
- **GraphQL Playground** : `/api/graphql/graphiql`

### Utilisateur courant

- `GET /api/me` - Informations de l'utilisateur connecté

## Permissions par rôle

| Rôle | Permissions |
|------|-------------|
| PUBLIC | GET sur actors, movies, categories, comments, directors |
| ROLE_USER | Créer des commentaires, gérer son profil, 2FA, API Key |
| ROLE_AUTHOR | Créer des films, modifier ses propres films |
| ROLE_EDITOR | Modifier tous les films et commentaires |
| ROLE_ADMIN | Gestion complète (actors, categories, directors, users) |

### Détail par entité

| Entité | GET | POST | PUT | DELETE |
|--------|-----|------|-----|--------|
| Actor | Public | ADMIN | ADMIN | ADMIN |
| Movie | Public | AUTHOR | EDITOR/Auteur | ADMIN/Auteur |
| Category | Public | ADMIN | ADMIN | ADMIN |
| Comment | Public | USER | EDITOR/Auteur | EDITOR/Auteur |
| Director | Public | ADMIN | ADMIN | ADMIN |
| User | Public | Public (inscription) | ADMIN/Self | ADMIN |

## GraphQL

L'API GraphQL est accessible à `/api/graphql`.

### Exemple de query

```graphql
query {
  movies {
    edges {
      node {
        id
        name
        description
        releaseDate
        categories {
          edges {
            node {
              name
            }
          }
        }
        actors {
          edges {
            node {
              firstname
              lastname
            }
          }
        }
      }
    }
  }
}
```

### Exemple de mutation

```graphql
mutation {
  createMovie(input: {
    name: "Nouveau Film"
    description: "Description du film"
    duration: 120
  }) {
    movie {
      id
      name
    }
  }
}
```

## Upload de médias

**Endpoint** : `POST /api/media_objects`

**Format** : `multipart/form-data`

**Champ** : `file`

```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -F "file=@image.jpg" \
  https://example.com/api/media_objects
```

Les fichiers sont stockés dans `public/uploads/media/`.

## Rate Limiting

L'API implémente un rate limiter par sliding window (1 heure) :

- **Utilisateurs anonymes** : 10 requêtes/heure (basé sur IP)
- **Utilisateurs authentifiés** : Limite personnalisable par utilisateur

Headers de réponse :
- `X-RateLimit-Limit` : Limite totale
- `X-RateLimit-Remaining` : Requêtes restantes
- `X-RateLimit-Reset` : Timestamp de réinitialisation

## Tests et qualité de code

```bash
# Tests unitaires
vendor/bin/phpunit

# PHP CodeSniffer (PSR-2)
vendor/bin/phpcs --standard=PSR2 src/

# PHPStan (analyse statique)
vendor/bin/phpstan analyze src/ --level=2

# PHPMD (mess detector)
vendor/bin/phpmd src/ text cleancode,codesize,controversial,design
```

## Structure du projet

```
src/
├── Controller/          # Controllers personnalisés
│   ├── ApiKeyController.php
│   ├── TwoFactorController.php
│   └── MeController.php
├── Entity/              # Entités Doctrine
│   ├── Actor.php
│   ├── Movie.php
│   ├── Category.php
│   ├── Comment.php
│   ├── Director.php
│   ├── User.php
│   └── MediaObject.php
├── Security/            # Authenticators
│   ├── CustomAuthenticator.php
│   └── ApiKeyAuthenticator.php
├── Service/             # Services métier
│   └── TwoFactorService.php
├── State/               # Processors API Platform
│   ├── UserPasswordHasher.php
│   ├── CommentProcessor.php
│   └── MovieProcessor.php
└── EventSubscriber/     # Event subscribers
    └── ApiRateLimitSubscriber.php
```

## Variables d'environnement

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | URL de connexion à la base de données |
| `JWT_SECRET_KEY` | Chemin vers la clé privée JWT |
| `JWT_PUBLIC_KEY` | Chemin vers la clé publique JWT |
| `JWT_PASSPHRASE` | Passphrase des clés JWT |
| `CORS_ALLOW_ORIGIN` | Origines autorisées pour CORS |

## Auteur

Noheila R. - BUT Informatique S5 - Université de Reims

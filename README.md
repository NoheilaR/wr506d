# WR506D - API Films

API REST et GraphQL pour la gestion de films, acteurs, catégories et commentaires.

## Démo en ligne

L'application est déployée sur un VPS et accessible à l'adresse suivante :

- **API Documentation** : https://mmi23c14.mmi-troyes.fr/wr506d/api/docs
- **API REST** : https://mmi23c14.mmi-troyes.fr/wr506d/api
- **GraphQL** : https://mmi23c14.mmi-troyes.fr/wr506d/api/graphql

## Technologies

- **PHP** : 8.4+
- **Framework** : Symfony 7.3
- **API** : API Platform 4.1 (REST + GraphQL)
- **Base de données** : MySQL/MariaDB (Doctrine ORM)
- **Authentification** : JWT (Lexik JWT Bundle) + API Key
- **Upload** : Vich Uploader Bundle
- **2FA** : TOTP avec Google Authenticator

> **Note** : La fonctionnalité 2FA nécessite l'extension PHP GD pour générer les QR codes. Le Dockerfile fourni inclut cette extension.

## Installation avec Docker (recommandé)

### 1. Créer le fichier `docker-compose.yml`

Dans un dossier parent, créez un fichier `docker-compose.yml` :

```yaml
version: '3.8'
services:
    web:
        build:
            context: ./www/html/wr506d
            dockerfile: Dockerfile
        container_name: symfony-web
        hostname: symfony-web
        restart: always
        ports:
            - 8319:80
        depends_on:
            - db
        volumes:
            - ./www/:/var/www/
            - ./sites/:/etc/apache2/sites-enabled/

    db:
        image: mariadb:10.8
        container_name: symfony-db
        hostname: symfony-db
        restart: always
        volumes:
            - db-volume:/var/lib/mysql
        environment:
            MYSQL_ROOT_PASSWORD: PASSWORD
            MYSQL_DATABASE: symfony
            MYSQL_USER: symfony
            MYSQL_PASSWORD: PASSWORD

    phpmyadmin:
        image: phpmyadmin/phpmyadmin
        container_name: symfony-adminsql
        hostname: symfony-adminsql
        restart: always
        ports:
            - 8080:80
        environment:
            PMA_HOST: db
            MYSQL_ROOT_PASSWORD: PASSWORD
            MYSQL_USER: symfony
            MYSQL_PASSWORD: PASSWORD
            MYSQL_DATABASE: symfony

    maildev:
        image: maildev/maildev
        container_name: symfony-mail
        hostname: symfony-mail
        command: bin/maildev --web 1080 --smtp 1025 --hide-extensions STARTTLS
        restart: always
        ports:
            - 1080:1080

volumes:
    db-volume:
```

### 2. Lancer les containers

```bash
docker-compose up -d
docker exec -ti symfony-web /root/init.sh
```

**Containers créés :**
| Container | Description | Accès |
|-----------|-------------|-------|
| `symfony-web` | Apache2, PHP 8.4, Composer, Symfony CLI, Node.js 20 | - |
| `symfony-db` | MariaDB (user: `symfony`, password: `PASSWORD`) | - |
| `symfony-adminsql` | PhpMyAdmin | `localhost:8080` |
| `symfony-mail` | MailDev | `localhost:1080` |

### 3. Cloner le projet

```bash
cd www/html
git clone https://github.com/NoheilaR/wr506d.git
```

### 4. Configuration Apache

Créez le fichier `sites/000-default.conf` :

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/wr506d/public

    <Directory /var/www/html/wr506d/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        allow from all
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### 5. Fichier `.htaccess`

Ajoutez dans `public/.htaccess` :

```apache
DirectoryIndex index.php

<IfModule mod_negotiation.c>
    Options -MultiViews
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]
    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]
    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
```

### 6. Configuration du projet

```bash
# Accéder au container
docker exec -ti symfony-web bash

# Aller dans le projet
cd /var/www/html/wr506d

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env .env.local
```

Dans `.env.local`, configurez la base de données :
```
DATABASE_URL="mysql://symfony:PASSWORD@symfony-db:3306/wr506d?serverVersion=mariadb-10.8.0"
```

```bash
# Créer la base de données et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# (Optionnel) Charger les fixtures
php bin/console doctrine:fixtures:load

# Générer les clés JWT
php bin/console lexik:jwt:generate-keypair

# Corriger les permissions pour les uploads
chmod -R 777 public/uploads/
```

L'API est accessible sur `http://localhost:8319/api`

---

## Installation manuelle (sans Docker)

### Prérequis

- PHP >= 8.4
- Composer
- MySQL/MariaDB
- OpenSSL (pour les clés JWT)
- Extension PHP GD (pour la génération des QR codes 2FA)

Pour installer GD sur Ubuntu/Debian :
```bash
sudo apt-get install php8.4-gd
sudo systemctl restart apache2
```

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

> **Prérequis** : L'extension PHP GD doit être installée pour générer les QR codes. Si vous utilisez Docker avec le Dockerfile fourni, GD est déjà inclus.

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

## Utilisation avec Postman

Une collection Postman est disponible pour tester l'API.

### Configuration des variables d'environnement

Dans Postman, créez un environnement avec les variables suivantes :

| Variable | Valeur | Description |
|----------|--------|-------------|
| `LOCAL_URL` | `http://localhost:8319` | URL de base de l'API |
| `TOKEN` | _(vide)_ | Token JWT (rempli automatiquement après login) |

### Authentification automatique

Pour récupérer automatiquement le token après login, ajoutez ce script dans l'onglet **Tests** de la requête `POST /auth` :

```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("TOKEN", jsonData.token);
}
```

### Utilisation du token

Dans les requêtes authentifiées, ajoutez le header :
- **Key** : `Authorization`
- **Value** : `Bearer {{TOKEN}}`

Ou configurez l'onglet **Authorization** :
- **Type** : Bearer Token
- **Token** : `{{TOKEN}}`

---

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

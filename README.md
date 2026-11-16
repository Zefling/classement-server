# Classement Server

REST API for managing tierlists, icebergs, bingos and other rankings.

## Quick Start

### Start the server

```bash
symfony server:start
```

#### Serving images with CORS in development

`symfony server:start` serves files under `public/` **statically**, bypassing PHP
and the `nelmio-cors` listener. As a result, images under `public/images/` are
returned **without** CORS headers. That is fine for normal browsing, but the
front-end's "export as image" feature (html2canvas with `useCORS`) needs to draw
those images onto a readable `<canvas>`, which requires the
`Access-Control-Allow-Origin` header — even on statically served files.

To test image export locally, run the bundled Apache dev server instead of
`symfony server:start`. Apache reads `public/.htaccess`, where a CORS rule is
applied to images:

```bash
./dev/serve-apache.sh          # serves http://localhost:8000 (Ctrl+C to stop)
APACHE_DEV_PORT=8001 ./dev/serve-apache.sh   # custom port
```

Notes:

- Stop `symfony server:start` first (both use port 8000 by default).
- Runs as the current user, no root required; runtime files go to `var/apache-dev/`.
- In production this is handled by the web server config (the same `.htaccess`
  CORS rule under Apache, or an equivalent `add_header` directive under nginx).

### Database

Create a new database migration:

```bash
php bin/console doctrine:migrations:diff
```

Apply database migrations:

```bash
php bin/console doctrine:migrations:migrate
```

### API Update

Clear cache:

```bash
sudo -u www-data php bin/console cache:clear
```

## API Documentation

- **[User Preferences API](docs/API_PREFERENCES.md)** - Complete documentation for the user preferences save and retrieve API

## Configuration

### Environment Variables

Copy `.env` to `.env.local` and configure the required variables:

```env
# Database
DATABASE_URL="mysql://user:password@127.0.0.1:3306/classement?serverVersion=mariadb-10.5.15&charset=utf8mb4"

# Preferences encryption key (generate with: php -r "echo bin2hex(random_bytes(32));")
APP_PREFERENCES_ENCRYPTION_KEY=your_32_byte_encryption_key_here

# OAuth (optional)
OAUTH_DISCORD_CLIENT_ID=
OAUTH_DISCORD_CLIENT_SECRET=
OAUTH_FACEBOOK_ID=
OAUTH_FACEBOOK_SECRET=
OAUTH_GOOGLE_CLIENT_ID=
OAUTH_GOOGLE_CLIENT_SECRET=

# TMDb API (optional)
TMDB_API_KEY=
```

## Tests

Run all tests:

```bash
php bin/phpunit
```

Run tests for a specific feature:

```bash
php bin/phpunit tests/Controller/Preferences/
```

## Project Structure

```
classement-server/
├── config/              # Symfony configuration
├── dev/                 # Local dev helpers (Apache dev server for CORS images)
├── migrations/          # Database migrations
├── public/              # Web entry point
├── src/
│   ├── Controller/      # API controllers
│   ├── Entity/          # Doctrine entities
│   ├── Repository/      # Doctrine repositories
│   ├── Service/         # Business services
│   ├── State/           # API state providers
│   └── Utils/           # Utils functions
├── tests/               # Unit and integration tests
│   └── Bruno/           # Bruno tests
└── vendor/              # Composer dependencies
```

## License

See the [LICENSE](LICENSE) file for details.

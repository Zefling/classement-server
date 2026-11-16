#!/usr/bin/env bash
# Local dev web server using Apache so that public/.htaccess (incl. the CORS
# rule on /images/) is honoured — unlike `symfony serve`, which serves static
# files directly and skips PHP/nelmio.
#
# Usage: ./dev/serve-apache.sh   (Ctrl+C to stop)

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUN_DIR="$PROJECT_DIR/var/apache-dev"
mkdir -p "$RUN_DIR"

export APACHE_DEV_DOCROOT="$PROJECT_DIR/public"
export APACHE_DEV_RUN="$RUN_DIR"
export APACHE_DEV_USER="$(id -un)"
export APACHE_DEV_GROUP="$(id -gn)"
export APACHE_DEV_PORT="${APACHE_DEV_PORT:-8000}"

echo "Serving $APACHE_DEV_DOCROOT on http://localhost:$APACHE_DEV_PORT (Apache, with .htaccess)"
echo "Logs: $RUN_DIR/error.log  —  Ctrl+C to stop"

# -D FOREGROUND keeps it attached to the terminal; -f uses our isolated config.
exec apache2 -D FOREGROUND -f "$PROJECT_DIR/dev/apache-dev.conf"

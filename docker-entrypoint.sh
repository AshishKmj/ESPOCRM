#!/bin/bash
set -e

# Default port (Render injects PORT, usually 10000)
export PORT="${PORT:-10000}"

echo "=========================================="
echo " Starting EspoCRM on Port: ${PORT}"
echo "=========================================="

# Configure Apache port
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf

# Parse DATABASE_URL if present (e.g., from Render PostgreSQL)
if [ -n "${DATABASE_URL:-}" ]; then
  echo "Found DATABASE_URL, parsing connection parameters..."
  eval $(php -r '
    $url = parse_url(getenv("DATABASE_URL"));
    if ($url) {
      $scheme = strtolower($url["scheme"] ?? "");
      $platform = (strpos($scheme, "mysql") !== false) ? "Mysql" : "Postgresql";
      echo "export ESPOCRM_DATABASE_PLATFORM=" . escapeshellarg($platform) . "\n";
      if (!empty($url["host"])) echo "export ESPOCRM_DATABASE_HOST=" . escapeshellarg($url["host"]) . "\n";
      if (!empty($url["port"])) echo "export ESPOCRM_DATABASE_PORT=" . escapeshellarg($url["port"]) . "\n";
      if (!empty($url["user"])) echo "export ESPOCRM_DATABASE_USER=" . escapeshellarg(urldecode($url["user"])) . "\n";
      if (!empty($url["pass"])) echo "export ESPOCRM_DATABASE_PASSWORD=" . escapeshellarg(urldecode($url["pass"])) . "\n";
      if (!empty($url["path"])) echo "export ESPOCRM_DATABASE_NAME=" . escapeshellarg(ltrim($url["path"], "/")) . "\n";
    }
  ')
fi

# Detect Site URL
if [ -z "${ESPOCRM_SITE_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
  export ESPOCRM_SITE_URL="${RENDER_EXTERNAL_URL}"
fi

# Default Platform
export ESPOCRM_DATABASE_PLATFORM="${ESPOCRM_DATABASE_PLATFORM:-Postgresql}"

# Default Port if missing
if [ -z "${ESPOCRM_DATABASE_PORT:-}" ]; then
  if [ "${ESPOCRM_DATABASE_PLATFORM}" = "Postgresql" ]; then
    export ESPOCRM_DATABASE_PORT="5432"
  else
    export ESPOCRM_DATABASE_PORT="3306"
  fi
fi

# Admin user defaults
export ESPOCRM_ADMIN_USERNAME="${ESPOCRM_ADMIN_USERNAME:-admin}"
export ESPOCRM_ADMIN_PASSWORD="${ESPOCRM_ADMIN_PASSWORD:-Admin@12345}"

# Ensure directories exist and have proper permissions
mkdir -p /var/www/html/data /var/www/html/custom /var/www/html/data/cache /var/www/html/data/logs
chown -R www-data:www-data /var/www/html/data /var/www/html/custom
chmod -R 775 /var/www/html/data /var/www/html/custom
chmod +x /var/www/html/bin/command

# Check installation status
IS_INSTALLED=false
if [ -f "/var/www/html/data/config.php" ]; then
  CHECK_STATUS=$(php -r '$c = @include("/var/www/html/data/config.php"); echo (!empty($c["isInstalled"])) ? "true" : "false";' 2>/dev/null || echo "false")
  if [ "$CHECK_STATUS" = "true" ]; then
    IS_INSTALLED=true
  fi
fi

if [ "$IS_INSTALLED" = "false" ] && [ -n "${ESPOCRM_DATABASE_HOST:-}" ] && [ -n "${ESPOCRM_DATABASE_NAME:-}" ]; then
  echo "--- EspoCRM is not yet installed. Initializing automated setup... ---"

  echo "1. Populating base configuration..."
  php /var/www/html/bin/command config:populate || true

  echo "2. Applying database parameters..."
  php /var/www/html/bin/command config:set "defaultPermissions.user" "www-data"
  php /var/www/html/bin/command config:set "defaultPermissions.group" "www-data"
  php /var/www/html/bin/command config:set database.platform "${ESPOCRM_DATABASE_PLATFORM}"
  php /var/www/html/bin/command config:set database.host "${ESPOCRM_DATABASE_HOST}"
  php /var/www/html/bin/command config:set --type=int database.port "${ESPOCRM_DATABASE_PORT}"
  php /var/www/html/bin/command config:set database.dbname "${ESPOCRM_DATABASE_NAME}"
  php /var/www/html/bin/command config:set database.user "${ESPOCRM_DATABASE_USER}"
  php /var/www/html/bin/command config:set database.password "${ESPOCRM_DATABASE_PASSWORD}"

  if [ -n "${ESPOCRM_SITE_URL:-}" ]; then
    php /var/www/html/bin/command config:set siteUrl "${ESPOCRM_SITE_URL}"
  fi

  echo "3. Waiting for database connection (${ESPOCRM_DATABASE_HOST}:${ESPOCRM_DATABASE_PORT})..."
  DB_READY=false
  for i in $(seq 1 30); do
    STATUS=$(php /var/www/html/bin/command db:check 2>/dev/null || echo "FAIL")
    if [ "$STATUS" = "OK" ]; then
      echo "Database connected successfully!"
      DB_READY=true
      break
    fi
    echo "Waiting for database connection (attempt $i/30)..."
    sleep 3
  done

  if [ "$DB_READY" = "true" ]; then
    echo "4. Building database schema & application rebuild..."
    php /var/www/html/bin/command rebuild || true

    echo "5. Creating admin account..."
    php /var/www/html/bin/command create-admin-user "${ESPOCRM_ADMIN_USERNAME}" || true
    printf '%s\n' "${ESPOCRM_ADMIN_PASSWORD}" | php /var/www/html/bin/command set-password "${ESPOCRM_ADMIN_USERNAME}" || true

    php /var/www/html/bin/command config:set --type=bool isInstalled true

    echo "========================================================="
    echo " EspoCRM Setup Complete!"
    echo " Username: ${ESPOCRM_ADMIN_USERNAME}"
    echo " Password: ${ESPOCRM_ADMIN_PASSWORD}"
    if [ -n "${ESPOCRM_SITE_URL:-}" ]; then
      echo " URL:      ${ESPOCRM_SITE_URL}"
    fi
    echo "========================================================="
  else
    echo "WARNING: Could not connect to database during boot. You can complete setup via the web wizard."
  fi

elif [ "$IS_INSTALLED" = "true" ]; then
  echo "--- EspoCRM is already installed. Running cache cleanup & updates... ---"
  php /var/www/html/bin/command clear-cache || true
  php /var/www/html/bin/command rebuild || true
else
  echo "--- Notice: Database credentials not fully provided. Web installation wizard is ready. ---"
fi

# Ensure final permissions
chown -R www-data:www-data /var/www/html/data /var/www/html/custom

# Start background cron worker (EspoCRM requires cron every minute)
echo "Starting background cron runner..."
(
  while true; do
    su -s /bin/bash www-data -c "php /var/www/html/cron.php" > /dev/null 2>&1 || true
    sleep 60
  done
) &

echo "Starting Apache web server..."
exec apache2-foreground

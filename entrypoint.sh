#!/bin/bash
set -e

# Render assigns a dynamic port via the $PORT environment variable.
# Standard Apache listens on port 80.
# We must rewrite the Apache configuration files to listen on $PORT instead.

if [ -n "$PORT" ]; then
    echo "Configuring Apache to listen on port $PORT..."
    sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf
else
    echo "No PORT environment variable detected. Defaulting to 80."
fi

# Make sure the server directory is fully writable by Apache (www-data)
# so that SQLite can create its .sqlite-wal and .sqlite-shm journal files
# if running locally without Postgres.
chown -R www-data:www-data /var/www/html/server

# Start Apache in the foreground
echo "Starting Apache..."
exec apache2-foreground

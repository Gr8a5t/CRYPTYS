FROM php:8.2-apache

# Install required system packages and PHP extensions for PostgreSQL & SQLite
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files to web root
COPY . /var/www/html/

# Make entrypoint script executable
RUN chmod +x /var/www/html/entrypoint.sh

# Set correct ownership for the web directory so Apache can write to SQLite database and handle sessions
RUN chown -R www-data:www-data /var/www/html

# Use the entrypoint script to dynamically configure the Apache port
ENTRYPOINT ["/var/www/html/entrypoint.sh"]

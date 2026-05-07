#!/bin/bash
set -e

# Railway provides $PORT — Apache must listen on it
PORT="${PORT:-80}"

# Update Apache ports
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/Listen 443/Listen 443/" /etc/apache2/ports.conf 2>/dev/null || true

# Inject PORT into virtual host config
sed -i "s/__PORT__/$PORT/g" /etc/apache2/sites-available/000-default.conf

# Ensure uploads directory is writable
mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html/uploads
chmod 775 /var/www/html/uploads

echo "Testing Apache configuration..."
apache2ctl configtest 2>&1

echo "Starting Apache on port $PORT..."
exec apache2-foreground 2>&1

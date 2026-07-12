#!/bin/bash
set -e

echo "==> Angelow Docker Entry Point"

# Wait for MySQL to be ready
echo "==> Waiting for MySQL..."
until php -r "
    try {
        new PDO('mysql:host=${DB_HOST};port=3306', '${DB_USER}', '${DB_PASS}');
        echo 'MySQL is ready!';
    } catch (PDOException \$e) {
        echo 'Waiting for MySQL...';
        exit(1);
    }
" 2>/dev/null; do
    echo "    MySQL not ready yet, retrying in 2s..."
    sleep 2
done
echo ""

# Install composer dependencies if vendor is missing
if [ ! -d "vendor" ]; then
    echo "==> Installing composer dependencies..."
    composer install --no-dev --no-interaction --optimize-autoloader
fi

# Ensure proper permissions
echo "==> Setting permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/public/assets 2>/dev/null || true
chmod -R 775 /var/www/html/public/uploads 2>/dev/null || true

echo "==> Starting Apache..."
exec apache2-foreground

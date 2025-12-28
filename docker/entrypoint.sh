#!/bin/sh

set -e

echo "Starting application initialization..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php -r "
try {
    \$pdo = new PDO(
        'mysql:host=db;port=3306;dbname=virgosoft',
        'virgosoft',
        'password'
    );
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->query('SELECT 1');
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    echo "Waiting for database connection..."
    sleep 2
done
echo "Database connection established!"


# Install Composer dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

#Run migrations
php artisan migrate

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run migrations (optional - comment out if you want to run manually)
# php artisan migrate --force

echo "Application initialization complete!"

# Start PHP-FPM
exec php-fpm


#!/usr/bin/env bash
# Start script for Render

echo "🚀 Starting Laravel application..."

# Clear any previous cache that might cause issues
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start the application
echo "🌐 Starting server on port ${PORT:-8000}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

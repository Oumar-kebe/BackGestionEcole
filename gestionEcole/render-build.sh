#!/usr/bin/env bash
# Build script for Render

set -o errexit  # Exit on error

echo "🚀 Starting Laravel build process..."

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Generate app key if not set
echo "🔑 Generating application key..."
php artisan key:generate --force

# Cache configuration
echo "⚡ Caching configuration..."
php artisan config:cache

# Cache routes (only if you have routes to cache)
echo "🛣️ Caching routes..."
php artisan route:cache || echo "No routes to cache"

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Cache views
echo "👀 Caching views..."
php artisan view:cache

# Create storage link (for file uploads)
echo "🔗 Creating storage link..."
php artisan storage:link || echo "Storage link already exists"

echo "✅ Build completed successfully!"

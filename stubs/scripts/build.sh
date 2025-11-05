#!/bin/bash
set -e

echo "🔨 Building Tauri-PHP application..."

# Load .env.tauri
if [ -f .env.tauri ]; then
    export $(cat .env.tauri | grep -v '^#' | xargs)
fi

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --production

# Optimize Laravel
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build Tauri
echo "🚀 Building Tauri application..."
npm run tauri build

echo "✅ Build completed!"
echo "📁 Artifacts location: src-tauri/target/release/bundle/"

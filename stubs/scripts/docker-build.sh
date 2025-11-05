#!/bin/bash
set -e

echo "🐳 Building Tauri-PHP application using Docker..."

# Load .env.tauri
if [ -f .env.tauri ]; then
    export $(cat .env.tauri | grep -v '^#' | xargs)
fi

PLATFORMS=${1:-"linux-x64,linux-arm64,windows-x64"}

echo "📦 Target platforms: $PLATFORMS"

# Build for each platform
IFS=',' read -ra PLATFORM_ARRAY <<< "$PLATFORMS"
for PLATFORM in "${PLATFORM_ARRAY[@]}"; do
    echo "Building for $PLATFORM..."
    php artisan tauri:build --platform=$PLATFORM
done

echo "✅ All builds completed!"

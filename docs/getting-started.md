# Getting Started with Tauri-PHP

This guide will help you create your first Tauri-PHP desktop application.

## Your First Desktop Application

### Step 1: Initialize Tauri

In your Laravel project root:

```bash
php artisan tauri:init
```

You'll be asked a few questions:
- **App Name**: Your application name (defaults to Laravel app name)
- **Identifier**: Bundle identifier (e.g., com.company.myapp)
- **Frontend Template**: Choose vanilla, vue, react, or svelte

The command creates:
- `.env.tauri` - Configuration file
- `src-tauri/` - Rust backend directory
- `desktop-frontend/` - Desktop app frontend
- `binaries/` - FrankenPHP binaries will be stored here

### Step 2: Configure Your App

Edit `.env.tauri` to customize your application:

```env
TAURI_APP_NAME="My Awesome App"
TAURI_WINDOW_WIDTH=1400
TAURI_WINDOW_HEIGHT=900
TAURI_PHP_VERSION=8.3
```

### Step 3: Start Development Mode

```bash
php artisan tauri:dev
```

This will:
1. Start Laravel's development server
2. Open your app in a native window
3. Enable hot reload for rapid development

### Step 4: Build Your Application

When ready to distribute:

```bash
php artisan tauri:build
```

Find your distributable app in:
```
src-tauri/target/release/bundle/
```

## Understanding the Project Structure

```
your-laravel-app/
├── .env.tauri              # Tauri configuration
├── src-tauri/              # Rust/Tauri backend
│   ├── src/
│   │   └── main.rs        # Rust entry point
│   ├── Cargo.toml         # Rust dependencies
│   └── tauri.conf.json    # Tauri configuration
├── desktop-frontend/       # Desktop app frontend (HTML/JS)
│   └── index.html
├── binaries/              # FrankenPHP binaries
└── tauri-builds/          # Final build artifacts
```

## Configuration Options

### Window Settings

```env
TAURI_WINDOW_TITLE="My App"
TAURI_WINDOW_WIDTH=1200
TAURI_WINDOW_HEIGHT=800
TAURI_WINDOW_RESIZABLE=true
TAURI_WINDOW_FULLSCREEN=false
```

### PHP Configuration

```env
TAURI_PHP_VERSION=8.3
TAURI_PHP_EXTENSIONS=opcache,pdo_sqlite,mbstring,openssl
TAURI_FRANKENPHP_VERSION=latest
```

### Build Options

```env
TAURI_BUILD_DEBUG=false
TAURI_OBFUSCATE_CODE=false
```

## Development Workflow

### 1. Make Changes to Your Laravel App

Edit your Laravel application as normal:
- Routes in `routes/web.php`
- Controllers in `app/Http/Controllers/`
- Views in `resources/views/`

### 2. Test in Development Mode

```bash
php artisan tauri:dev
```

Changes to your Laravel app will hot-reload automatically.

### 3. Customize Desktop Frontend (Optional)

Edit `desktop-frontend/index.html` to customize the loading screen or add desktop-specific features.

### 4. Build for Distribution

```bash
php artisan tauri:build
```

## Common Tasks

### Change Window Size

Edit `.env.tauri`:
```env
TAURI_WINDOW_WIDTH=1600
TAURI_WINDOW_HEIGHT=1000
```

### Add PHP Extensions

Edit `.env.tauri`:
```env
TAURI_PHP_EXTENSIONS=opcache,pdo_sqlite,mbstring,openssl,redis,imagick
```

### Build for Specific Platform

```bash
# Windows
php artisan tauri:build --platform=windows-x64

# macOS (Intel)
php artisan tauri:build --platform=macos-x64

# macOS (Apple Silicon)
php artisan tauri:build --platform=macos-arm64

# Linux
php artisan tauri:build --platform=linux-x64
```

### Enable Code Obfuscation

```bash
php artisan tauri:build --obfuscate
```

### Clean Build Artifacts

```bash
# Clean build artifacts only
php artisan tauri:clean

# Clean everything including dependencies
php artisan tauri:clean --all
```

## Tips and Best Practices

### 1. Use SQLite for Desktop Apps

Desktop apps work best with SQLite:

```env
# .env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

### 2. Optimize for Production

Before building:

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The build command does this automatically.

### 3. Handle Desktop-Specific Logic

Check if running in Tauri:

```php
if (env('TAURI_EMBEDDED', false)) {
    // Desktop-specific logic
}
```

### 4. Manage File Storage

Use Laravel's storage system:

```php
// Stores files in storage/app
Storage::disk('local')->put('file.txt', $contents);
```

### 5. Test Thoroughly

Test your app in development mode before building:

```bash
php artisan tauri:dev
```

## Next Steps

- Read the [Configuration Guide](configuration.md)
- Learn about [Building for Distribution](building.md)
- Check [Troubleshooting](troubleshooting.md) if you encounter issues

## Need Help?

- [GitHub Issues](https://github.com/mucan54/tauri-php/issues)
- [GitHub Discussions](https://github.com/mucan54/tauri-php/discussions)
- [Documentation](https://github.com/mucan54/tauri-php/tree/main/docs)

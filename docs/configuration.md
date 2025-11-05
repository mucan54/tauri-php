# Configuration Reference

Complete reference for all Tauri-PHP configuration options.

## .env.tauri File

All Tauri-specific configuration is stored in `.env.tauri` at your project root.

### Application Information

```env
TAURI_APP_NAME="My Desktop App"
TAURI_APP_IDENTIFIER=com.example.myapp
TAURI_APP_VERSION=0.1.0
```

- `TAURI_APP_NAME`: Display name of your application
- `TAURI_APP_IDENTIFIER`: Unique bundle identifier (reverse domain notation)
- `TAURI_APP_VERSION`: Application version (semantic versioning)

### Window Configuration

```env
TAURI_WINDOW_TITLE="My Desktop App"
TAURI_WINDOW_WIDTH=1200
TAURI_WINDOW_HEIGHT=800
TAURI_WINDOW_RESIZABLE=true
TAURI_WINDOW_FULLSCREEN=false
```

### Development Server

```env
TAURI_DEV_HOST=127.0.0.1
TAURI_DEV_PORT=8080
```

### FrankenPHP Configuration

```env
TAURI_FRANKENPHP_VERSION=latest
TAURI_PHP_VERSION=8.3
TAURI_PHP_EXTENSIONS=opcache,pdo_sqlite,mbstring,openssl,tokenizer
```

Available PHP versions: `8.1`, `8.2`, `8.3`

Common extensions:
- `opcache` - PHP code caching
- `pdo_sqlite` - SQLite database
- `mbstring` - Multibyte string support
- `openssl` - Cryptography
- `tokenizer` - PHP tokenization
- `xml` - XML parsing
- `ctype` - Character type checking
- `json` - JSON encoding/decoding
- `bcmath` - Arbitrary precision math
- `fileinfo` - File type detection
- `gd` - Image processing
- `redis` - Redis support
- `imagick` - Advanced image processing

### Build Settings

```env
TAURI_BUILD_DEBUG=false
TAURI_OBFUSCATE_CODE=false
```

### Code Signing

```env
# macOS
TAURI_SIGN_MACOS=false
TAURI_MACOS_SIGNING_IDENTITY="Developer ID Application: Your Name (TEAM_ID)"

# Windows
TAURI_SIGN_WINDOWS=false
TAURI_WINDOWS_CERTIFICATE_THUMBPRINT=your-thumbprint
```

## config/tauri-php.php

Advanced configuration file (published with `--tag=tauri-php-config`).

### Platform Targets

```php
'platforms' => [
    'linux-x64' => 'x86_64-unknown-linux-gnu',
    'linux-arm64' => 'aarch64-unknown-linux-gnu',
    'macos-x64' => 'x86_64-apple-darwin',
    'macos-arm64' => 'aarch64-apple-darwin',
    'windows-x64' => 'x86_64-pc-windows-msvc',
],
```

### Obfuscation Settings

```php
'obfuscation' => [
    'enabled' => env('TAURI_OBFUSCATE_CODE', false),
    'tool' => env('TAURI_OBFUSCATE_TOOL', 'yakpro-po'),
    'exclude_paths' => [
        'vendor',
        'storage',
        'bootstrap/cache',
    ],
],
```

### Build Paths

```php
'paths' => [
    'tauri_dir' => env('TAURI_DIR', 'src-tauri'),
    'frontend_dir' => env('TAURI_FRONTEND_DIR', 'desktop-frontend'),
    'binaries_dir' => env('TAURI_BINARIES_DIR', 'binaries'),
    'temp_dir' => env('TAURI_TEMP_DIR', 'tauri-temp'),
    'build_output_dir' => env('TAURI_BUILD_OUTPUT_DIR', 'tauri-builds'),
],
```

## Environment-Specific Configuration

### Development

```env
TAURI_BUILD_DEBUG=true
TAURI_OBFUSCATE_CODE=false
```

### Production

```env
TAURI_BUILD_DEBUG=false
TAURI_OBFUSCATE_CODE=true
```

## Best Practices

1. **Never commit sensitive data** in `.env.tauri`
2. **Use version control** for `.env.tauri.example`
3. **Document custom settings** for your team
4. **Test configuration changes** in development first
5. **Keep PHP extensions minimal** for smaller builds

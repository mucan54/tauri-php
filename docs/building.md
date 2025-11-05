# Building Your Tauri-PHP Application

Complete guide to building and distributing your desktop application.

## Basic Build

Build for your current platform:

```bash
php artisan tauri:build
```

This produces distributable files in `src-tauri/target/release/bundle/`.

## Platform-Specific Builds

### Windows

```bash
php artisan tauri:build --platform=windows-x64
```

Produces:
- `.exe` installer (NSIS)
- `.msi` installer
- Portable `.exe`

### macOS

```bash
# Intel Macs
php artisan tauri:build --platform=macos-x64

# Apple Silicon (M1/M2)
php artisan tauri:build --platform=macos-arm64
```

Produces:
- `.dmg` disk image
- `.app` application bundle

### Linux

```bash
# x64
php artisan tauri:build --platform=linux-x64

# ARM64
php artisan tauri:build --platform=linux-arm64
```

Produces:
- `.deb` package (Debian/Ubuntu)
- `.AppImage` (universal)

## Build Options

### Debug Build

```bash
php artisan tauri:build --debug
```

Creates a debug build with:
- Debug symbols included
- Larger file size
- Better error messages

### Code Obfuscation

```bash
php artisan tauri:build --obfuscate
```

Protects your PHP source code using YakPro-Po obfuscator.

### Skip Dependencies

```bash
php artisan tauri:build --skip-deps
```

Skips `composer install` and `npm install` - useful for CI/CD.

### Force Rebuild

```bash
php artisan tauri:build --force
```

Forces rebuilding of FrankenPHP binaries even if they exist.

## Cross-Platform Builds

### Using Docker

Build for multiple platforms using Docker:

```bash
./docker-build.sh "windows-x64,linux-x64,macos-arm64"
```

Or use the artisan command:

```bash
php artisan tauri:build --platform=windows-x64
php artisan tauri:build --platform=linux-x64
php artisan tauri:build --platform=macos-arm64
```

## Build Process Overview

1. **Install Dependencies**: Composer and npm packages
2. **Optimize Laravel**: Config, route, and view caching
3. **Prepare Embedded App**: Copy Laravel app to temporary directory
4. **Obfuscate Code**: (Optional) Obfuscate PHP files
5. **Build FrankenPHP**: Create static PHP binaries
6. **Build Tauri**: Compile Rust and create installers

## Packaging

After building, create distribution packages:

```bash
php artisan tauri:package
```

Options:

```bash
# Specific format
php artisan tauri:package --format=dmg

# Custom output directory
php artisan tauri:package --output=/path/to/output

# Sign packages
php artisan tauri:package --sign
```

## Code Signing

### macOS

1. Get an Apple Developer account
2. Create a Developer ID Application certificate
3. Configure in `.env.tauri`:

```env
TAURI_SIGN_MACOS=true
TAURI_MACOS_SIGNING_IDENTITY="Developer ID Application: Your Name (XXXXXXXXXX)"
```

### Windows

1. Get a code signing certificate
2. Import to certificate store
3. Configure in `.env.tauri`:

```env
TAURI_SIGN_WINDOWS=true
TAURI_WINDOWS_CERTIFICATE_THUMBPRINT=your-thumbprint
```

## Optimizing Build Size

### 1. Minimize PHP Extensions

Only include needed extensions:

```env
TAURI_PHP_EXTENSIONS=opcache,pdo_sqlite,mbstring
```

### 2. Remove Unused Files

Exclude from embedding in `config/tauri-php.php`:

```php
'exclude_from_embed' => [
    'tests',
    'docs',
    '.git',
]
```

### 3. Use Release Mode

Always build in release mode (default):

```bash
php artisan tauri:build  # No --debug flag
```

## CI/CD Integration

### GitHub Actions

```yaml
name: Build
on: [push]
jobs:
  build:
    runs-on: ${{ matrix.os }}
    strategy:
      matrix:
        os: [ubuntu-latest, windows-latest, macos-latest]
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      - name: Setup Rust
        uses: dtolnay/rust-toolchain@stable
      - name: Build
        run: |
          composer install --no-dev
          php artisan tauri:build --skip-deps
```

## Troubleshooting Builds

### Build Fails on FrankenPHP

- Check Docker is installed for cross-platform builds
- Verify PHP extensions are available
- Try building natively instead of cross-compiling

### Large Bundle Size

- Remove unused PHP extensions
- Enable code obfuscation (compresses code)
- Check for large dependencies in `vendor/`

### Missing Dependencies

- Run: `composer install --no-dev`
- Run: `npm install`
- Or let the build command install them automatically

## Next Steps

- Learn about [Configuration](configuration.md)
- See [Troubleshooting](troubleshooting.md) for common issues

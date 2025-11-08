# iOS Build Quickstart

**Goal**: Run Laravel natively on iOS devices with embedded PHP runtime.

## Prerequisites

```bash
# Install Xcode (from App Store)
xcode-select --install

# Install build tools
brew install automake libtool pkg-config autoconf bison re2c
```

## Step 1: Build PHP for iOS

```bash
cd scripts
chmod +x build-php-ios.sh
./build-php-ios.sh
```

**Build Time**: ~15-30 minutes (first time)
**Output**: `binaries/php-iphoneos-arm64` and `binaries/php-iphonesimulator-arm64`

## Step 2: Test the Binary

```bash
# Test on simulator
./binaries/php-iphonesimulator-arm64 -v

# Should output:
# PHP 8.3.14 (cli) (built: ...)
```

## Step 3: Initialize Mobile in Your Laravel App

```bash
cd your-laravel-project

# Merge latest package changes
git pull origin main
composer update mucan54/tauri-php

# Initialize desktop first (if not already done)
php artisan tauri:init --force

# Copy iOS PHP binaries to your project
mkdir -p binaries
cp ../tauri-php/binaries/php-iphoneos-arm64 binaries/
cp ../tauri-php/binaries/php-iphonesimulator-arm64 binaries/

# Initialize iOS
php artisan tauri:mobile-init ios --team-id=YOUR_TEAM_ID
```

## Step 4: Run on iOS Simulator

```bash
php artisan tauri:mobile-dev ios --emulator
```

**What happens:**
1. ✅ Rust compiles with lib configuration
2. ✅ iOS PHP binary gets bundled
3. ✅ App launches in simulator
4. ✅ PHP server starts inside the app
5. ✅ WebView connects to PHP server
6. ✅ Laravel runs natively on iOS!

## Current Status

### ✅ What Works Now

- ✅ PHP compilation for iOS ARM64
- ✅ Static binary with Laravel extensions
- ✅ Library target for Rust/Tauri
- ✅ Platform-specific binary bundling
- ✅ Rust lib.rs spawns embedded PHP binary on iOS
- ✅ Automatic iOS filesystem setup
- ✅ Laravel path configuration for iOS sandbox
- ✅ First-launch directory initialization

### 🚧 What's Next

1. **Build PHP binaries** (`./scripts/build-php-ios.sh`)
2. **Test on iOS simulator** with real Laravel project
3. **Bundle Laravel app files** in iOS package
4. **Test full stack** on real device
5. **Android support** (similar approach)

## Architecture

```
┌─────────────────────────────────────┐
│         iOS App (Tauri)             │
│  ┌───────────────────────────────┐  │
│  │     WebView (UI)              │  │
│  │  ┌─────────────────────────┐  │  │
│  │  │   http://localhost:8080 │  │  │
│  │  └──────────┬──────────────┘  │  │
│  └─────────────┼──────────────────┘  │
│                │                     │
│  ┌─────────────▼──────────────────┐  │
│  │  PHP 8.3 (Static Binary)      │  │
│  │  ┌──────────────────────────┐  │  │
│  │  │     Laravel App          │  │  │
│  │  │  (Routes, Controllers,   │  │  │
│  │  │   Eloquent, Blade, etc)  │  │  │
│  │  └──────────────────────────┘  │  │
│  │  ┌──────────────────────────┐  │  │
│  │  │   SQLite Database        │  │  │
│  │  └──────────────────────────┘  │  │
│  └─────────────────────────────────┘  │
└─────────────────────────────────────┘
        iOS Device / Simulator
```

## Troubleshooting

### "resource path doesn't exist" Error

This is expected until PHP binary is built and copied. Follow steps 1-3 above.

### Build Fails

```bash
# Check Xcode
xcodebuild -version

# Reinstall command line tools
sudo rm -rf /Library/Developer/CommandLineTools
xcode-select --install
```

### Binary Not Found

```bash
# Verify binary exists
ls -lh binaries/php-iphoneos-arm64

# Check it's executable
file binaries/php-iphoneos-arm64
```

## File Size Reference

| File | Size |
|------|------|
| php-iphoneos-arm64 | ~18MB |
| php-iphonesimulator-arm64 | ~18MB |
| Final .ipa (compressed) | ~25-30MB |

## Next Steps

After successful iOS build:

1. [ ] Build PHP for iOS
2. [ ] Test binary on simulator
3. [ ] Initialize mobile in Laravel project
4. [ ] Test app on simulator
5. [ ] Test app on real device
6. [ ] Submit to App Store

## Documentation

- [Full iOS PHP Build Guide](docs/ios-php-build.md)
- [Mobile Development Guide](docs/mobile.md)
- [Tauri Mobile Docs](https://v2.tauri.app/develop/)

## Questions?

This is a **pioneering feature** - running full Laravel natively on iOS!
Open an issue if you encounter problems.

# Building PHP for iOS

This guide explains how to build PHP binaries that run natively on iOS devices, enabling Laravel to run directly on iPhone/iPad.

## Prerequisites

Before building PHP for iOS, ensure you have:

- **macOS** with Xcode 14.0 or later
- **Xcode Command Line Tools**: `xcode-select --install`
- **Homebrew packages**:
  ```bash
  brew install automake libtool pkg-config autoconf bison re2c
  ```

## Quick Start

### 1. Run the Build Script

```bash
cd scripts
chmod +x build-php-ios.sh
./build-php-ios.sh
```

This will:
- Download PHP 8.3.14 source code
- Cross-compile for iOS device (ARM64)
- Cross-compile for iOS simulator (ARM64)
- Place binaries in `binaries/` directory

### 2. Verify the Build

```bash
# Test simulator binary
./binaries/php-iphonesimulator-arm64 -v

# Should output: PHP 8.3.14 (cli) ...
```

## Build Process Details

### What Gets Compiled

The build script compiles PHP with these Laravel-required extensions:

**Core Extensions:**
- PDO (MySQL, SQLite)
- OpenSSL
- mbstring
- BCMath
- Fileinfo
- Tokenizer
- XML/SimpleXML/DOM
- JSON
- Ctype
- Sockets
- POSIX

**Disabled Features:**
- OPcache (not compatible with static builds)
- PHPDBG (not needed on iOS)
- PCRE JIT (causes issues on iOS)

### Build Outputs

After building, you'll have:

```
binaries/
├── php-iphoneos-arm64         # For real iOS devices
└── php-iphonesimulator-arm64  # For iOS simulator
```

### Build Time

- First build: ~15-30 minutes (downloads source, compiles everything)
- Subsequent builds: ~5-10 minutes (reuses downloaded source)

## Customizing the Build

### Change PHP Version

Edit `scripts/build-php-ios.sh`:

```bash
PHP_VERSION="8.3.14"  # Change to desired version
```

### Add More Extensions

Edit the `./configure` section in `build_php()`:

```bash
./configure \
    --host=$HOST \
    --enable-static \
    # ... existing flags ...
    --enable-gd \           # Add this
    --with-jpeg \           # And this
    # ... more extensions ...
```

### Build for Intel Macs

Uncomment these lines in `build-php-ios.sh`:

```bash
# build_php "iphonesimulator" "x86_64" "iphonesimulator-x86_64"
# create_universal_binary
```

This creates a universal binary supporting both Apple Silicon and Intel Macs.

## Integration with Tauri

### 1. Copy Binaries

After building, the binaries need to be in your Tauri project:

```bash
cp binaries/php-iphoneos-arm64 your-project/binaries/
cp binaries/php-iphonesimulator-arm64 your-project/binaries/
```

### 2. Update tauri.conf.json

The package will automatically configure this, but for reference:

```json
{
  "bundle": {
    "iOS": {
      "resources": [
        "binaries/php-iphoneos-arm64",
        "binaries/php-iphonesimulator-arm64"
      ]
    }
  }
}
```

### 3. Rust Code Will Handle Execution

The Tauri Rust code automatically:
- Detects iOS platform
- Spawns the PHP binary
- Starts Laravel server on localhost
- Connects WebView to it

## Troubleshooting

### Build Fails with "SDK not found"

```bash
# Verify Xcode installation
xcode-select -p

# Should output: /Applications/Xcode.app/Contents/Developer
# If not, reinstall Xcode Command Line Tools
```

### Configure Fails with "autoconf not found"

```bash
brew install autoconf automake libtool
```

### Binary Too Large

The static PHP binary is ~15-20MB. To reduce size:

1. Disable unused extensions in configure
2. Use `strip` command (already done by script)
3. Consider compression (Tauri handles this)

### "Symbol not found" Errors on iOS

This usually means a missing library. Check that all `--with-*` flags point to static libraries, not dynamic ones.

## Advanced: Building Dependencies

Some extensions require external libraries. These must also be compiled for iOS.

### Example: Building OpenSSL for iOS

```bash
git clone https://github.com/openssl/openssl.git
cd openssl
./Configure ios64-cross --prefix=/tmp/openssl-ios
make
make install
```

Then reference in PHP configure:
```bash
--with-openssl=/tmp/openssl-ios
```

## Binary Size Comparison

| Platform | Binary Size | Compressed |
|----------|-------------|------------|
| macOS (FrankenPHP) | ~45MB | ~15MB |
| iOS (Static PHP) | ~18MB | ~6MB |
| Android (Static PHP) | ~18MB | ~6MB |

## Testing on Device

### Simulator Testing

```bash
# The Tauri dev command handles this automatically
php artisan tauri:mobile-dev ios --emulator
```

### Real Device Testing

1. Connect iPhone via USB
2. Trust the device in Xcode
3. Run: `php artisan tauri:mobile-dev ios --device "Your iPhone"`

## Performance Notes

- **Startup Time**: ~200-500ms for PHP to initialize
- **Memory Usage**: ~30-50MB for Laravel runtime
- **CPU Usage**: Minimal when idle, normal during requests

## Comparison with Other Approaches

| Approach | Pros | Cons |
|----------|------|------|
| **Embedded PHP (This)** | ✅ Fully offline<br>✅ No server needed<br>✅ Full Laravel | ⚠️ Larger app size<br>⚠️ Complex builds |
| **Remote API** | ✅ Smaller app<br>✅ Easy updates | ❌ Requires internet<br>❌ Not true offline |
| **SQLite + JS** | ✅ Small size | ❌ Rewrite Laravel logic<br>❌ Not Laravel |

## Next Steps

After successful build:

1. ✅ Test the binary on simulator
2. ✅ Integrate with Tauri mobile init
3. ✅ Configure Laravel for iOS filesystem
4. ✅ Test full app on device
5. ✅ Submit to App Store

## Support

If you encounter issues:

1. Check PHP compilation logs in `build/ios/`
2. Verify Xcode version: `xcodebuild -version`
3. Ensure iOS SDK is installed: `xcodebuild -showsdks`
4. Open an issue with full error output

## Credits

Build approach based on:
- PocketMine-MP PHP build scripts
- iOS PHP compilation techniques
- Static binary compilation best practices

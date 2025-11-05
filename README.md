# Tauri-PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mucan54/tauri-php.svg?style=flat-square)](https://packagist.org/packages/mucan54/tauri-php)
[![Total Downloads](https://img.shields.io/packagist/dt/mucan54/tauri-php.svg?style=flat-square)](https://packagist.org/packages/mucan54/tauri-php)
[![License](https://img.shields.io/packagist/l/mucan54/tauri-php.svg?style=flat-square)](https://packagist.org/packages/mucan54/tauri-php)

Transform your Laravel applications into beautiful, fast, and secure cross-platform desktop and mobile applications using Tauri and FrankenPHP.

## ✨ Features

- 🚀 **One-Command Initialization** - Get started with a single Artisan command
- 🖥️ **Desktop Support** - Build for Windows, macOS, and Linux from a single codebase
- 📱 **Mobile Support** - Build native Android and iOS applications
- 📦 **Embedded PHP** - No PHP installation required on user devices thanks to FrankenPHP
- 🔒 **Code Protection** - Built-in PHP code obfuscation support
- ⚡ **Hot Reload** - Fast development with hot reload support
- 🎨 **Framework Agnostic** - Works with Vue, React, Svelte, or vanilla JavaScript
- 🐳 **Docker Support** - Cross-compile for different platforms using Docker
- 🔧 **Easy Configuration** - Simple `.env.tauri` configuration file

## 📋 Prerequisites

Before using Tauri-PHP, ensure you have the following installed:

- **PHP 8.1 or higher**
- **Laravel 10.x or 11.x**
- **Node.js 18+** and npm
- **Rust and Cargo** - Install from [rustup.rs](https://rustup.rs/)
- **Docker** (optional, for cross-platform builds)

## 📦 Installation

Install the package via Composer:

```bash
composer require mucan54/tauri-php
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=tauri-php-config
```

## 🚀 Quick Start

### 1. Initialize Tauri in Your Laravel Project

```bash
php artisan tauri:init
```

This command will:
- Create `.env.tauri` configuration file
- Set up the Tauri project structure
- Install necessary dependencies
- Generate Rust backend code
- Create the desktop frontend

### 2. Start Development Server

```bash
php artisan tauri:dev
```

This opens your Laravel app in a native desktop window with hot reload support.

### 3. Build for Production

```bash
# Build for current platform
php artisan tauri:build

# Build for specific platform
php artisan tauri:build --platform=windows-x64

# Build with code obfuscation
php artisan tauri:build --obfuscate
```

Your distributable application will be in `src-tauri/target/release/bundle/`.

## 📖 Documentation

For detailed documentation, visit our [documentation directory](docs/):

- [Installation Guide](docs/installation.md)
- [Getting Started](docs/getting-started.md)
- [Mobile Development](docs/mobile.md) 📱 **NEW!**
- [Configuration Reference](docs/configuration.md)
- [Building Applications](docs/building.md)
- [Troubleshooting](docs/troubleshooting.md)

## 🎯 Available Commands

### Desktop Commands

| Command | Description |
|---------|-------------|
| `php artisan tauri:init` | Initialize Tauri in your Laravel project |
| `php artisan tauri:dev` | Start development server with hot reload |
| `php artisan tauri:build` | Build the application for production |
| `php artisan tauri:package` | Create distribution packages |
| `php artisan tauri:clean` | Clean build artifacts and temporary files |

### Mobile Commands

| Command | Description |
|---------|-------------|
| `php artisan tauri:mobile-init {platform}` | Initialize mobile platform (android/ios/both) |
| `php artisan tauri:mobile-dev {platform}` | Run app on mobile device/emulator |
| `php artisan tauri:build --platform=android` | Build Android application (APK/AAB) |
| `php artisan tauri:build --platform=ios` | Build iOS application |

## ⚙️ Configuration

The package uses a `.env.tauri` file for configuration. Here are the key settings:

```env
# Application Info
TAURI_APP_NAME="My Desktop App"
TAURI_APP_IDENTIFIER=com.example.myapp
TAURI_APP_VERSION=0.1.0

# Window Settings
TAURI_WINDOW_TITLE="My Desktop App"
TAURI_WINDOW_WIDTH=1200
TAURI_WINDOW_HEIGHT=800

# Development Server
TAURI_DEV_HOST=127.0.0.1
TAURI_DEV_PORT=8080

# FrankenPHP Settings
TAURI_FRANKENPHP_VERSION=latest
TAURI_PHP_VERSION=8.3
TAURI_PHP_EXTENSIONS=opcache,pdo_sqlite,mbstring,openssl,tokenizer,xml,ctype,json,bcmath,fileinfo

# Build Settings
TAURI_BUILD_DEBUG=false
TAURI_OBFUSCATE_CODE=false
```

## 🏗️ How It Works

1. **FrankenPHP Embedding**: Your Laravel application is embedded with a standalone FrankenPHP binary
2. **Tauri Wrapper**: A lightweight Rust/Tauri application wraps your Laravel app in a native window
3. **No External Dependencies**: Users don't need PHP, a web server, or any dependencies installed
4. **Native Performance**: Leverages native webviews for optimal performance and small bundle sizes

## 🌍 Supported Platforms

### Desktop Platforms

| Platform | Target Triple | Status |
|----------|--------------|--------|
| Linux x64 | `x86_64-unknown-linux-gnu` | ✅ Supported |
| Linux ARM64 | `aarch64-unknown-linux-gnu` | ✅ Supported |
| macOS x64 | `x86_64-apple-darwin` | ✅ Supported |
| macOS ARM64 (M1/M2) | `aarch64-apple-darwin` | ✅ Supported |
| Windows x64 | `x86_64-pc-windows-msvc` | ✅ Supported |

### Mobile Platforms

| Platform | Target Triple | Status |
|----------|--------------|--------|
| Android (ARM64) | `aarch64-linux-android` | ✅ Supported |
| Android (ARMv7) | `armv7-linux-androideabi` | ✅ Supported |
| Android (x86_64) | `x86_64-linux-android` | ✅ Supported |
| iOS (ARM64) | `aarch64-apple-ios` | ✅ Supported |
| iOS Simulator | `aarch64-apple-ios-sim` | ✅ Supported |

## 🔒 Code Obfuscation

Protect your source code when distributing:

```bash
php artisan tauri:build --obfuscate
```

This uses YakPro-Po by default to obfuscate your PHP code before embedding.

## 🐳 Cross-Platform Building

Build for multiple platforms using Docker:

```bash
# Build for specific platform
php artisan tauri:build --platform=windows-x64

# Build for multiple platforms
./docker-build.sh "linux-x64,windows-x64,macos-arm64"
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 🙏 Credits

- Built on top of [Tauri](https://tauri.app/) - The Rust-powered desktop framework
- Uses [FrankenPHP](https://frankenphp.dev/) - The modern PHP app server
- Inspired by the Laravel community

## 📧 Support

- **GitHub Issues**: [Report bugs or request features](https://github.com/mucan54/tauri-php/issues)
- **Discussions**: [Ask questions and share ideas](https://github.com/mucan54/tauri-php/discussions)

## 🌟 Show Your Support

If this package helped you, please give it a ⭐️ on [GitHub](https://github.com/mucan54/tauri-php)!

---

**Made with ❤️ for the Laravel community**

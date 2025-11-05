# Installation Guide

This guide will walk you through installing Tauri-PHP and its prerequisites.

## System Requirements

### Minimum Requirements

- **PHP**: 8.1, 8.2, or 8.3
- **Laravel**: 10.x or 11.x
- **Node.js**: 18.x or higher
- **npm**: 9.x or higher
- **Rust**: Latest stable version
- **Operating System**: Windows 10+, macOS 10.15+, or Linux (Ubuntu 20.04+)

### Recommended

- **RAM**: 8GB or more
- **Disk Space**: 10GB free space for build tools and artifacts
- **Docker**: For cross-platform builds (optional)

## Installing Prerequisites

### 1. Install Node.js and npm

#### Windows
Download and install from [nodejs.org](https://nodejs.org/)

#### macOS
```bash
# Using Homebrew
brew install node

# Or download from nodejs.org
```

#### Linux
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install nodejs npm

# Fedora
sudo dnf install nodejs npm

# Arch Linux
sudo pacman -S nodejs npm
```

Verify installation:
```bash
node --version  # Should be 18.x or higher
npm --version   # Should be 9.x or higher
```

### 2. Install Rust and Cargo

Run the following command on all platforms:

```bash
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
```

Follow the on-screen instructions. After installation:

```bash
# Reload your shell configuration
source $HOME/.cargo/env

# Verify installation
rustc --version
cargo --version
```

### 3. Install Docker (Optional)

Docker is only needed for cross-platform builds.

- **Windows**: [Docker Desktop for Windows](https://docs.docker.com/desktop/install/windows-install/)
- **macOS**: [Docker Desktop for Mac](https://docs.docker.com/desktop/install/mac-install/)
- **Linux**: Follow [Docker Engine installation](https://docs.docker.com/engine/install/)

Verify Docker:
```bash
docker --version
```

## Installing Tauri-PHP

### 1. Install via Composer

In your Laravel project directory:

```bash
composer require mucan54/tauri-php
```

### 2. Publish Configuration (Optional)

To customize the default configuration:

```bash
php artisan vendor:publish --tag=tauri-php-config
```

This creates `config/tauri-php.php` in your Laravel project.

### 3. Publish Stubs (Optional)

To customize the template files:

```bash
php artisan vendor:publish --tag=tauri-php-stubs
```

This creates a `stubs/tauri-php/` directory with all template files.

## Verifying Installation

Run the following commands to verify everything is installed correctly:

```bash
# Check PHP version
php --version

# Check Laravel version
php artisan --version

# Check Node.js
node --version

# Check npm
npm --version

# Check Rust
rustc --version
cargo --version

# Check Tauri-PHP installation
php artisan tauri:init --help
```

If all commands run successfully, you're ready to use Tauri-PHP!

## Platform-Specific Notes

### Windows

1. **Visual Studio Build Tools**: Required for Rust compilation
   - Download from [Visual Studio Downloads](https://visualstudio.microsoft.com/downloads/)
   - Select "Desktop development with C++"

2. **WebView2**: Automatically installed on Windows 10/11

3. **Path Configuration**: Ensure Rust and Cargo are in your PATH

### macOS

1. **Xcode Command Line Tools**: Required
   ```bash
   xcode-select --install
   ```

2. **Homebrew**: Recommended for managing dependencies
   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```

### Linux

1. **System Dependencies**:
   ```bash
   # Ubuntu/Debian
   sudo apt install build-essential curl wget libssl-dev libgtk-3-dev libwebkit2gtk-4.0-dev libayatana-appindicator3-dev librsvg2-dev

   # Fedora
   sudo dnf install gcc-c++ curl wget openssl-devel gtk3-devel webkit2gtk4.0-devel libappindicator-gtk3-devel librsvg2-devel

   # Arch Linux
   sudo pacman -S base-devel curl wget openssl gtk3 webkit2gtk libappindicator-gtk3 librsvg
   ```

## Troubleshooting Installation

### Rust Installation Issues

If Rust doesn't install correctly:

1. Check your internet connection
2. Try manual installation from [rust-lang.org](https://www.rust-lang.org/tools/install)
3. Ensure your shell configuration is updated

### Node.js Version Conflicts

If you have multiple Node.js versions:

1. Use [nvm](https://github.com/nvm-sh/nvm) to manage versions
2. Set the correct version:
   ```bash
   nvm install 18
   nvm use 18
   ```

### Permission Issues on Linux/macOS

If you encounter permission errors:

```bash
# Fix npm permissions
mkdir ~/.npm-global
npm config set prefix '~/.npm-global'
echo 'export PATH=~/.npm-global/bin:$PATH' >> ~/.bashrc
source ~/.bashrc
```

### Docker Issues

If Docker builds fail:

1. Ensure Docker daemon is running
2. Check Docker permissions (Linux):
   ```bash
   sudo usermod -aG docker $USER
   newgrp docker
   ```
3. Verify Docker can run:
   ```bash
   docker run hello-world
   ```

## Next Steps

Once installation is complete:

1. Read the [Getting Started Guide](getting-started.md)
2. Initialize your first Tauri-PHP project:
   ```bash
   php artisan tauri:init
   ```
3. Start development:
   ```bash
   php artisan tauri:dev
   ```

## Getting Help

If you encounter issues:

- Check the [Troubleshooting Guide](troubleshooting.md)
- Search [GitHub Issues](https://github.com/mucan54/tauri-php/issues)
- Ask in [GitHub Discussions](https://github.com/mucan54/tauri-php/discussions)

# Troubleshooting Guide

Common issues and their solutions.

## Installation Issues

### "Command not found: cargo"

**Problem**: Rust/Cargo not installed or not in PATH

**Solution**:
```bash
# Install Rust
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh

# Reload shell
source $HOME/.cargo/env
```

### "Command not found: npm"

**Problem**: Node.js not installed

**Solution**: Install Node.js from [nodejs.org](https://nodejs.org/)

## Build Issues

### FrankenPHP Build Fails

**Problem**: Docker not available or build script errors

**Solution**:
1. Check Docker is running: `docker ps`
2. Try native build instead of cross-platform
3. Check PHP extensions are valid

### "Binary verification failed"

**Problem**: Built binary is corrupted or incomplete

**Solution**:
```bash
# Force rebuild
php artisan tauri:build --force

# Check disk space
df -h
```

### Build Hangs or Times Out

**Problem**: Build process stuck

**Solution**:
1. Increase timeout in `BuildCommand.php`
2. Check internet connection
3. Verify system resources (RAM, CPU)

## Development Issues

### "Server not ready"

**Problem**: Laravel dev server not starting

**Solution**:
```bash
# Check if port is in use
lsof -i :8080  # macOS/Linux
netstat -ano | findstr :8080  # Windows

# Try different port
php artisan tauri:dev --port=8081
```

### Hot Reload Not Working

**Problem**: Changes not reflecting

**Solution**:
1. Restart dev server: `php artisan tauri:dev`
2. Clear Laravel cache: `php artisan cache:clear`
3. Check browser console for errors

## Runtime Issues

### Application Won't Start

**Problem**: Desktop app crashes on launch

**Solution**:
1. Check logs in development mode
2. Verify all dependencies are included
3. Test embedded Laravel app separately

### Database Errors

**Problem**: SQLite database not found

**Solution**:
```bash
# Create database directory
mkdir -p database

# Create database file
touch database/database.sqlite

# Run migrations
php artisan migrate
```

### Missing PHP Extensions

**Problem**: PHP extension not loaded

**Solution**: Add to `.env.tauri`:
```env
TAURI_PHP_EXTENSIONS=opcache,pdo_sqlite,mbstring,your-extension
```

Then rebuild:
```bash
php artisan tauri:build --force
```

## Platform-Specific Issues

### Windows

**Build Error: "MSVC not found"**
- Install Visual Studio Build Tools
- Select "Desktop development with C++"

**Antivirus Blocking Build**
- Add project directory to exclusions
- Temporarily disable real-time protection

### macOS

**"Cannot be opened because the developer cannot be verified"**
- Right-click app → Open
- Or disable Gatekeeper: `xattr -cr YourApp.app`

**Codesign Errors**
- Verify signing identity: `security find-identity -v -p codesigning`
- Check certificate is valid

### Linux

**Missing System Libraries**

Ubuntu/Debian:
```bash
sudo apt install libwebkit2gtk-4.0-dev libgtk-3-dev
```

Fedora:
```bash
sudo dnf install webkit2gtk4.0-devel gtk3-devel
```

## Performance Issues

### Slow Build Times

**Solutions**:
1. Use native builds instead of Docker
2. Enable parallel building
3. Use SSD for build directory
4. Increase system RAM

### Large Bundle Size

**Solutions**:
1. Minimize PHP extensions
2. Remove unused Composer packages
3. Enable code obfuscation (compresses code)
4. Use production build (--no-debug)

## Getting Help

### Check Logs

Development logs:
```bash
php artisan tauri:dev --verbose
```

Build logs:
```bash
php artisan tauri:build --verbose
```

### Community Support

- [GitHub Issues](https://github.com/mucan54/tauri-php/issues)
- [GitHub Discussions](https://github.com/mucan54/tauri-php/discussions)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/tauri-php)

### Reporting Bugs

Include:
1. Operating system and version
2. PHP version: `php --version`
3. Node.js version: `node --version`
4. Rust version: `rustc --version`
5. Error messages and logs
6. Steps to reproduce

## Still Having Issues?

Try:
1. Clean and rebuild: `php artisan tauri:clean --all && php artisan tauri:build`
2. Update dependencies: `composer update && npm update`
3. Check official documentation
4. Search existing GitHub issues

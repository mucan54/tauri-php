# Changelog

All notable changes to `mucan54/tauri-php` will be documented in this file.

## [1.1.0] - 2024-11-05

### Added
- 📱 **Mobile Platform Support** - Full Android and iOS support
- `tauri:mobile-init` command for initializing mobile platforms
- `tauri:mobile-dev` command for mobile development with hot reload
- Android build support (APK/AAB) with code signing
- iOS build support with Xcode integration
- Mobile prerequisites validation (Java, Android SDK/NDK, Xcode, CocoaPods)
- Automatic emulator/simulator management
- Mobile-specific configuration in `.env.tauri`
- Comprehensive mobile development guide (`docs/mobile.md`)
- Support for Android (ARM64, ARMv7, x86_64, x86) and iOS (ARM64, Simulator)

### Enhanced
- Extended `tauri:build` command to support `--platform=android` and `--platform=ios`
- Added mobile platform targets to configuration
- Updated README with mobile commands and platform support

## [1.0.0] - 2024-11-05

### Added
- Initial release
- `tauri:init` command for project initialization
- `tauri:build` command for building applications
- `tauri:dev` command for development mode
- `tauri:package` command for creating distribution packages
- `tauri:clean` command for cleaning build artifacts
- Support for Windows, macOS, and Linux platforms
- FrankenPHP integration for embedded PHP runtime
- Code obfuscation support
- Docker-based cross-compilation support
- Hot reload development mode
- Environment-based configuration via `.env.tauri`
- Support for Vue, React, Svelte, and vanilla JavaScript frontends

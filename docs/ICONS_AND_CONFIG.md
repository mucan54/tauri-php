# Icons and Tauri Configuration Guide

## Adding Laravel Icons to Your Project

### Icons Already Included

This package includes Laravel icons downloaded from Wikimedia Commons in the `icons/` directory:
- 32x32.png
- 128x128.png
- 128x128@2x.png (256x256)
- icon-512.png (512x512)

You'll need to generate platform-specific formats (.icns for macOS, .ico for Windows) using the Tauri CLI or online converters. See `icons/README.md` for details.

## Fixing tauri.conf.json - Common Errors

### ❌ Error: `externalBin` in Wrong Location

```
Error `tauri.conf.json` error on `bundle > iOS`: Additional properties are not allowed ('externalBin' was unexpected)
Error `tauri.conf.json` error on `bundle > android`: Additional properties are not allowed ('externalBin' was unexpected)
```

### Problem

The `externalBin` property is being placed under `bundle > iOS`, `bundle > android`, etc., but it belongs at the top level of the config, NOT inside bundle platform properties.

### ❌ Incorrect Configuration:

```json
{
  "bundle": {
    "iOS": {
      "externalBin": ["bin/php"]  // ❌ ERROR: not allowed here
    },
    "android": {
      "externalBin": ["bin/php"]  // ❌ ERROR: not allowed here
    },
    "windows": {
      "externalBin": ["bin/php"]  // ❌ ERROR: not allowed here
    }
  }
}
```

### ✅ Correct Configuration:

```json
{
  "productName": "Your App",
  "version": "0.1.0",
  "identifier": "com.yourcompany.yourapp",
  "build": {
    "beforeDevCommand": "npm run dev",
    "beforeBuildCommand": "npm run build",
    "frontendDist": "../dist"
  },
  "bundle": {
    "active": true,
    "targets": "all",
    "externalBin": ["bin/php"],  // ✅ CORRECT: top-level bundle property
    "icon": [
      "icons/32x32.png",
      "icons/128x128.png",
      "icons/128x128@2x.png",
      "icons/icon.icns",
      "icons/icon.ico"
    ],
    "iOS": {
      "minimumSystemVersion": "14.0"
      // ❌ DO NOT put externalBin here
    },
    "android": {
      "minSdkVersion": 24
      // ❌ DO NOT put externalBin here
    }
  }
}
```

## Complete Example tauri.conf.json

```json
{
  "$schema": "https://schema.tauri.app/config/2",
  "productName": "Laravel Tauri App",
  "version": "0.1.0",
  "identifier": "com.yourcompany.laravelapp",
  "build": {
    "beforeDevCommand": "npm run dev",
    "beforeBuildCommand": "npm run build",
    "devUrl": "http://localhost:5173",
    "frontendDist": "../dist"
  },
  "bundle": {
    "active": true,
    "targets": "all",
    "externalBin": ["bin/php"],
    "icon": [
      "icons/32x32.png",
      "icons/128x128.png",
      "icons/128x128@2x.png",
      "icons/icon.icns",
      "icons/icon.ico"
    ],
    "identifier": "com.yourcompany.laravelapp",
    "publisher": "Your Company",
    "copyright": "Copyright (c) 2025 Your Company",
    "category": "DeveloperTool",
    "shortDescription": "Laravel mobile application",
    "longDescription": "A Laravel application running on mobile devices with Tauri",
    "iOS": {
      "minimumSystemVersion": "14.0",
      "developmentTeam": "YOUR_TEAM_ID"
    },
    "android": {
      "minSdkVersion": 24
    },
    "windows": {
      "certificateThumbprint": null,
      "digestAlgorithm": "sha256",
      "timestampUrl": ""
    },
    "macOS": {
      "minimumSystemVersion": "10.13"
    },
    "linux": {
      "deb": {
        "depends": []
      }
    }
  },
  "app": {
    "windows": [
      {
        "title": "Laravel Tauri App",
        "width": 1024,
        "height": 768,
        "resizable": true,
        "fullscreen": false
      }
    ],
    "security": {
      "csp": null
    }
  },
  "plugins": {}
}
```

## Key Points

1. **`externalBin`** goes under `bundle`, NOT under `bundle.iOS`, `bundle.android`, etc.
2. **Icons** should be placed in the `icons/` directory at your project root
3. Platform-specific settings (like `minimumSystemVersion` for iOS) go under their respective platform keys
4. Use `tauri icon icon-512.png` to automatically generate all required icon formats

## tauri.conf.json Schema Reference

- Top level: `productName`, `version`, `identifier`, `build`, `bundle`, `app`, `plugins`
- Under `bundle`: `active`, `targets`, `externalBin`, `icon`, `identifier`, and platform keys
- Platform keys: `iOS`, `android`, `windows`, `macOS`, `linux`
- Platform keys contain platform-specific settings only, NOT cross-platform settings like `externalBin`

## Validation

To validate your `tauri.conf.json`:

```bash
# The Tauri CLI will validate on any command
npm run tauri build
npm run tauri dev
npm run tauri ios init
```

Errors will clearly indicate which properties are not allowed in which locations.

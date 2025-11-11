# Icons Directory

This directory contains Laravel icons for Tauri mobile builds.

## Current Status

✅ **PNG icons downloaded from Wikimedia Commons:**
- `32x32.png` - Small icon (32x32)
- `128x128.png` - Medium icon (128x128)
- `128x128@2x.png` - Retina medium icon (256x256)
- `icon-512.png` - High resolution icon (512x512)
- `laravel-source.png` - Original source (1200px)

Source: https://commons.wikimedia.org/wiki/File:Laravel.svg

## Generating Platform-Specific Icons

### For macOS (.icns)

You'll need to generate `icon.icns` for macOS builds:

**Option 1: Using Tauri CLI (Recommended)**
```bash
npm install -g @tauri-apps/cli
tauri icon icon-512.png
```

**Option 2: Using iconutil (macOS only)**
```bash
# Create iconset directory
mkdir icon.iconset
cp 32x32.png icon.iconset/icon_32x32.png
cp 128x128.png icon.iconset/icon_128x128.png
cp 128x128@2x.png icon.iconset/icon_128x128@2x.png
# ... add other sizes

# Generate .icns
iconutil -c icns icon.iconset -o icon.icns
```

**Option 3: Online converter**
- https://cloudconvert.com/png-to-icns

### For Windows (.ico)

You'll need to generate `icon.ico` for Windows builds:

**Option 1: Using Tauri CLI (Recommended)**
```bash
npm install -g @tauri-apps/cli
tauri icon icon-512.png
```

**Option 2: Online converter**
- https://cloudconvert.com/png-to-ico
- https://convertico.com/

**Option 3: ImageMagick**
```bash
convert icon-512.png -define icon:auto-resize=256,128,96,64,48,32,16 icon.ico
```

## Configuration

Add icons to your `tauri.conf.json`:

```json
{
  "bundle": {
    "icon": [
      "icons/32x32.png",
      "icons/128x128.png",
      "icons/128x128@2x.png",
      "icons/icon.icns",
      "icons/icon.ico"
    ]
  }
}
```

See [docs/ICONS_AND_CONFIG.md](../docs/ICONS_AND_CONFIG.md) for complete configuration guide.

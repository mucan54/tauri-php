# Tauri-PHP Mobile Plugins Integration Guide

This guide explains how to integrate and use the complete mobile plugin system in your Tauri-PHP Laravel application.

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Frontend Setup](#frontend-setup)
4. [Backend Setup](#backend-setup)
5. [Native Implementation](#native-implementation)
6. [Usage Examples](#usage-examples)
7. [Troubleshooting](#troubleshooting)

## Overview

The Tauri-PHP mobile plugin system provides a complete bridge between Laravel backend and Tauri mobile plugins, enabling you to use native mobile features like:

- **Camera**: Take photos, access gallery, multiple photo selection
- **Notification**: Schedule local notifications, manage notification channels
- **Vibration**: Haptic feedback, vibration patterns
- **Geolocation**: GPS location tracking, position monitoring
- **Storage**: Persistent key-value storage

### Architecture

```
┌─────────────────┐
│  Laravel PHP    │ ←→ Queue plugin calls in Cache
│  (Backend)      │
└─────────────────┘
        ↑
        │ HTTP API
        ↓
┌─────────────────┐
│  JavaScript     │ ←→ Poll for calls, execute via Tauri
│  Bridge         │
└─────────────────┘
        ↑
        │ Tauri invoke()
        ↓
┌─────────────────┐
│  Rust Plugin    │ ←→ Route to platform-specific code
│  (Tauri)        │
└─────────────────┘
        ↑
        │ FFI / Native APIs
        ↓
┌─────────────────┐
│  Swift/Kotlin   │ ←→ Native mobile functionality
│  (iOS/Android)  │
└─────────────────┘
```

## Installation

### 1. Install the Package

```bash
composer require mucan54/tauri-php
```

### 2. Publish Package Files

```bash
# Publish configuration
php artisan vendor:publish --tag=tauri-php-config

# Publish routes
php artisan vendor:publish --tag=tauri-php-routes

# Publish stubs (frontend bridge, native code, examples)
php artisan vendor:publish --tag=tauri-php-stubs
```

### 3. Configure Your Application

Add to your `.env`:

```env
# Enable specific plugins
TAURI_PLUGIN_CAMERA=true
TAURI_PLUGIN_NOTIFICATION=true
TAURI_PLUGIN_VIBRATION=true
TAURI_PLUGIN_GEOLOCATION=true
TAURI_PLUGIN_STORAGE=true

# Plugin settings
TAURI_PLUGIN_TIMEOUT=30
TAURI_PLUGIN_POLLING_INTERVAL=500
```

## Frontend Setup

### 1. Copy the JavaScript Bridge

Copy the bridge file to your frontend project:

```bash
cp stubs/tauri-php/frontend/tauri-bridge.js resources/js/
```

### 2. Include in Your App

Add to your main JavaScript file (e.g., `resources/js/app.js`):

```javascript
import './tauri-bridge.js';
```

Or include directly in your HTML:

```html
<script type="module" src="/js/tauri-bridge.js"></script>
```

### 3. Ensure CSRF Token

Make sure your HTML includes the CSRF token meta tag:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### 4. Build Your Frontend

```bash
npm run build
# or for development
npm run dev
```

## Backend Setup

### 1. Routes

The package automatically registers API routes at `/api/tauri/*`:

- `POST /api/tauri/mark-active` - Mark Tauri environment as active
- `GET /api/tauri/plugin-calls` - Get pending plugin calls
- `POST /api/tauri/plugin-response` - Receive plugin results

These routes are already configured and require no additional setup.

### 2. Using Plugins in Controllers

#### Option A: Using Facades (Recommended)

```php
use Mucan54\TauriPhp\Facades\Camera;
use Mucan54\TauriPhp\Facades\Notification;
use Mucan54\TauriPhp\Facades\Vibration;

class MyController extends Controller
{
    public function takePhoto()
    {
        $photo = Camera::takePhoto(['quality' => 90]);
        return response()->json(['path' => $photo->getPath()]);
    }
}
```

#### Option B: Using Dependency Injection

```php
use Mucan54\TauriPhp\Plugins\Camera\Camera;

class MyController extends Controller
{
    public function takePhoto(Camera $camera)
    {
        $photo = $camera->takePhoto(['quality' => 90]);
        return response()->json(['path' => $photo->getPath()]);
    }
}
```

### 3. Example Implementation

Copy the example controller to your app:

```bash
cp stubs/tauri-php/examples/MobilePluginExampleController.php app/Http/Controllers/
```

Copy the example routes:

```bash
# Add the contents to your routes/api.php or routes/web.php
cat stubs/tauri-php/examples/mobile-example-routes.php >> routes/api.php
```

## Native Implementation

### iOS (Swift)

1. **Copy Plugin Files**

```bash
# Copy Swift implementation
cp -r stubs/tauri-php/tauri/plugins/camera/ios/Sources/* \
    src-tauri/gen/apple/Sources/
```

2. **Register Plugin in Tauri**

Edit `src-tauri/src/lib.rs`:

```rust
use tauri::Manager;

#[cfg(mobile)]
mod mobile;

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_camera::init())
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
```

3. **Configure Permissions**

Edit `src-tauri/gen/apple/project.yml` and add:

```yaml
targets:
  YourApp_iOS:
    info:
      properties:
        NSCameraUsageDescription: "We need access to your camera to take photos"
        NSPhotoLibraryUsageDescription: "We need access to your photo library"
        NSPhotoLibraryAddUsageDescription: "We need to save photos to your library"
```

### Android (Kotlin)

1. **Copy Plugin Files**

```bash
# Copy Kotlin implementation
cp stubs/tauri-php/tauri/plugins/camera/android/src/main/java/CameraPlugin.kt \
    src-tauri/gen/android/app/src/main/java/com/tauri/plugin/camera/
```

2. **Register Plugin**

The plugin will be automatically detected by Tauri's plugin system.

3. **Configure Permissions**

Edit `src-tauri/gen/android/app/src/main/AndroidManifest.xml`:

```xml
<manifest>
    <uses-permission android:name="android.permission.CAMERA" />
    <uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
    <uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
    <uses-permission android:name="android.permission.READ_MEDIA_IMAGES" />

    <!-- For FileProvider (camera images) -->
    <application>
        <provider
            android:name="androidx.core.content.FileProvider"
            android:authorities="${applicationId}.fileprovider"
            android:exported="false"
            android:grantUriPermissions="true">
            <meta-data
                android:name="android.support.FILE_PROVIDER_PATHS"
                android:resource="@xml/file_paths" />
        </provider>
    </application>
</manifest>
```

4. **Create FileProvider Configuration**

Create `src-tauri/gen/android/app/src/main/res/xml/file_paths.xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<paths xmlns:android="http://schemas.android.com/apk/res/android">
    <cache-path name="camera_images" path="/" />
</paths>
```

## Usage Examples

### Camera Plugin

```php
use Mucan54\TauriPhp\Facades\Camera;

// Take a photo
$photo = Camera::takePhoto([
    'quality' => 90,
    'allowEditing' => true,
    'resultType' => 'uri',
    'width' => 1920,
    'height' => 1080,
]);

// Save to storage
$photo->saveTo(storage_path('app/photos/photo.jpg'));

// Get as base64
$base64 = $photo->toBase64();

// Pick from gallery
$photo = Camera::pickPhoto(['quality' => 85]);

// Pick multiple photos
$photos = Camera::pickMultiplePhotos(['limit' => 5]);
```

### Notification Plugin

```php
use Mucan54\TauriPhp\Facades\Notification;

// Schedule a notification
Notification::schedule([
    'title' => 'Reminder',
    'body' => 'Don\'t forget your meeting!',
    'schedule' => [
        'at' => now()->addHours(1)->toIso8601String(),
    ],
]);

// Show instant notification
Notification::schedule([
    'title' => 'Hello!',
    'body' => 'Welcome to our app',
    'schedule' => [
        'at' => now()->toIso8601String(),
    ],
]);

// Get pending notifications
$pending = Notification::getPending();

// Cancel a notification
Notification::cancel(123);
```

### Vibration Plugin

```php
use Mucan54\TauriPhp\Facades\Vibration;

// Simple vibration
Vibration::vibrate(300); // 300ms

// Pattern vibration
Vibration::vibratePattern([100, 200, 100, 200, 100]);

// Haptic feedback
Vibration::impact('medium'); // light, medium, heavy
Vibration::notification('success'); // success, warning, error
Vibration::selection(); // Subtle tick
```

### Geolocation Plugin

```php
use Mucan54\TauriPhp\Facades\Geolocation;

// Get current position
$position = Geolocation::getCurrentPosition([
    'enableHighAccuracy' => true,
    'timeout' => 10000,
]);

echo $position->latitude;
echo $position->longitude;
echo $position->accuracy;

// Watch position (continuous tracking)
$watchId = Geolocation::watchPosition([
    'enableHighAccuracy' => true,
]);

// Clear watch
Geolocation::clearWatch($watchId);
```

### Storage Plugin

```php
use Mucan54\TauriPhp\Facades\Storage;

// Store data
Storage::set('user_name', 'John Doe');

// Get data
$name = Storage::get('user_name', 'Default Name');

// Store multiple items
Storage::setMultiple([
    'user_name' => 'John',
    'user_email' => 'john@example.com',
    'user_age' => 25,
]);

// Get multiple items
$data = Storage::getMultiple(['user_name', 'user_email']);

// Get all keys
$keys = Storage::keys();

// Remove item
Storage::remove('user_name');

// Clear all
Storage::clear();
```

### Permissions

```php
use Mucan54\TauriPhp\Facades\Camera;

// Check permissions
$permissions = Camera::checkPermissions();
// Returns: ['camera' => 'granted', 'photos' => 'denied']

// Request permissions
$permissions = Camera::requestPermissions();
```

## Troubleshooting

### Plugin calls timing out

**Problem**: Plugin calls throw timeout errors after 30 seconds.

**Solution**: Increase timeout in `config/tauri-php.php`:

```php
'plugins' => [
    'timeout' => 60, // Increase to 60 seconds
],
```

### "Tauri environment not detected" error

**Problem**: PHP can't detect the Tauri mobile environment.

**Solutions**:
1. Ensure the JavaScript bridge is loaded and running
2. Check that `/api/tauri/mark-active` is being called
3. Verify session middleware is enabled on routes
4. Check browser console for JavaScript errors

### Permissions denied on iOS

**Problem**: iOS denies camera or photo permissions.

**Solution**:
1. Check Info.plist usage descriptions are present
2. Ensure permissions are requested before use
3. Test on a real device (simulator may have limitations)

### Permissions denied on Android

**Problem**: Android denies permissions.

**Solution**:
1. Verify AndroidManifest.xml has correct permissions
2. For Android 13+, use READ_MEDIA_IMAGES instead of READ_EXTERNAL_STORAGE
3. Test with appropriate target SDK version

### JavaScript bridge not polling

**Problem**: Plugin calls don't execute.

**Solution**:
1. Check browser console for errors
2. Verify `/api/tauri/plugin-calls` endpoint is accessible
3. Check CSRF token is present
4. Verify polling interval in config

### Swift/Kotlin compilation errors

**Problem**: Native code doesn't compile.

**Solution**:
1. Ensure all import statements are correct
2. Check Xcode/Android Studio for detailed errors
3. Verify target platform versions match your app
4. Clean and rebuild: `cargo tauri android/ios build --debug`

## Testing

### Test in Browser DevTools

Open browser DevTools in your Tauri app and test the bridge:

```javascript
// Check if bridge is active
console.log(window.tauriPhpBridge);

// Manually call a plugin
window.tauriPhpBridge.call('camera', 'takePhoto', { quality: 90 })
    .then(result => console.log(result));
```

### Test Laravel Endpoints

```bash
# Check if Tauri routes are registered
php artisan route:list | grep tauri

# Test the mark-active endpoint
curl -X POST http://localhost:8080/api/tauri/mark-active \
    -H "Content-Type: application/json" \
    -d '{"active":true}'
```

## Advanced Configuration

### Custom Plugin Defaults

Edit `config/tauri-php.php`:

```php
'plugins' => [
    'defaults' => [
        'camera' => [
            'quality' => 95,
            'resultType' => 'base64',
        ],
        'geolocation' => [
            'enableHighAccuracy' => true,
            'timeout' => 15000,
        ],
    ],
],
```

### Custom Polling Interval

```php
'plugins' => [
    'polling_interval' => 1000, // 1 second (slower but less CPU)
],
```

### Disable Specific Plugins

```php
'plugins' => [
    'enabled' => [
        'camera' => true,
        'notification' => false, // Disable notifications
        'vibration' => true,
        'geolocation' => true,
        'storage' => true,
    ],
],
```

## Support

For issues, questions, or contributions:

- GitHub: https://github.com/mucan54/tauri-php
- Documentation: https://github.com/mucan54/tauri-php/docs

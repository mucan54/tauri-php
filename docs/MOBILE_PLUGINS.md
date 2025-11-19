# Tauri-PHP Mobile Plugins

This package provides PHP wrappers for Tauri mobile plugins, allowing you to access native mobile features from your Laravel application.

## Available Plugins

1. [Camera](#camera-plugin) - Take photos, access gallery
2. [Notification](#notification-plugin) - Local push notifications
3. [Vibration](#vibration-plugin) - Haptic feedback
4. [Geolocation](#geolocation-plugin) - GPS and location services
5. [Storage](#storage-plugin) - Persistent key-value storage

---

## Installation

The plugins are included with the tauri-php package. To use them, you need to:

1. Initialize Tauri for mobile:
```bash
php artisan tauri:mobile-init ios
```

2. Create plugin instances in your Laravel code:
```php
use Mucan54\TauriPhp\Plugins\Camera\Camera;
use Mucan54\TauriPhp\Plugins\Notification\Notification;

$camera = new Camera();
$notification = new Notification();
```

---

## Camera Plugin

Access device camera and photo gallery.

### Taking Photos

```php
use Mucan54\TauriPhp\Plugins\Camera\Camera;

$camera = new Camera();

// Take a photo
$result = $camera->takePhoto([
    'quality' => 90,
    'allowEditing' => true,
    'saveToGallery' => true,
]);

// Get photo path
$photoPath = $result->getPath();

// Save to specific location
$result->saveTo(storage_path('photos/user-photo.jpg'));

// Get as base64
$base64 = $result->toBase64();
```

### Picking from Gallery

```php
// Pick single photo
$result = $camera->pickPhoto([
    'quality' => 80,
]);

// Pick multiple photos
$photos = $camera->pickMultiplePhotos([
    'limit' => 5,
]);

foreach ($photos as $photo) {
    $photo->saveTo(storage_path("photos/{$photo->path}"));
}
```

### Camera Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `quality` | int | 90 | Image quality (0-100) |
| `allowEditing` | bool | false | Allow user to crop/edit |
| `resultType` | string | 'uri' | 'base64', 'uri', or 'dataUrl' |
| `saveToGallery` | bool | false | Save to device gallery |
| `correctOrientation` | bool | true | Auto-rotate based on EXIF |
| `width` | int | null | Max width in pixels |
| `height` | int | null | Max height in pixels |

### Permissions

```php
// Check permissions
$permissions = $camera->checkPermissions();

// Request permissions
$camera->requestPermissions();
```

---

## Notification Plugin

Schedule and manage local push notifications.

### Basic Notification

```php
use Mucan54\TauriPhp\Plugins\Notification\Notification;

$notification = new Notification();

// Schedule a notification
$notification->schedule([
    'title' => 'Hello!',
    'body' => 'This is a test notification',
    'id' => 1,
]);
```

### Scheduled Notifications

```php
// Schedule for specific time
$notification->schedule([
    'title' => 'Reminder',
    'body' => 'Time to check your app!',
    'id' => 2,
    'schedule' => [
        'at' => now()->addHours(2)->timestamp * 1000, // milliseconds
    ],
]);

// Repeating notification
$notification->schedule([
    'title' => 'Daily Reminder',
    'body' => 'Don\'t forget!',
    'id' => 3,
    'schedule' => [
        'every' => 'day',
        'at' => '09:00',
    ],
]);
```

### Managing Notifications

```php
// Get pending notifications
$pending = $notification->getPending();

// Cancel a notification
$notification->cancel(1);

// Cancel multiple
$notification->cancelMultiple([1, 2, 3]);

// Cancel all
$notification->cancelAll();

// Get delivered notifications
$delivered = $notification->getDelivered();

// Remove delivered
$notification->removeDelivered([1, 2]);
```

### Notification Channels (Android)

```php
// Create a channel
$notification->createChannel([
    'id' => 'important',
    'name' => 'Important Notifications',
    'description' => 'High priority notifications',
    'importance' => 5,
    'sound' => 'notification.wav',
    'vibration' => true,
]);

// Use the channel
$notification->schedule([
    'title' => 'Important!',
    'body' => 'This is important',
    'channelId' => 'important',
]);

// List channels
$channels = $notification->listChannels();

// Delete a channel
$notification->deleteChannel('important');
```

### Notification Options

| Option | Type | Description |
|--------|------|-------------|
| `title` | string | Notification title (required) |
| `body` | string | Notification body text |
| `id` | int | Unique notification ID |
| `schedule` | array | When to show the notification |
| `sound` | string | Sound file name |
| `badge` | int | Badge number (iOS) |
| `icon` | string | Icon name (Android) |
| `channelId` | string | Channel ID (Android) |
| `autoCancel` | bool | Auto dismiss on tap |
| `ongoing` | bool | Persistent notification |

---

## Vibration Plugin

Trigger device vibration and haptic feedback.

### Basic Vibration

```php
use Mucan54\TauriPhp\Plugins\Vibration\Vibration;

$vibration = new Vibration();

// Simple vibration
$vibration->vibrate(300); // 300ms

// Vibration pattern
$vibration->vibratePattern([100, 200, 100]); // vibrate-pause-vibrate

// Cancel vibration
$vibration->cancel();
```

### Haptic Feedback (iOS Taptic Engine)

```php
// Impact feedback
$vibration->impact('light');   // light tap
$vibration->impact('medium');  // medium tap
$vibration->impact('heavy');   // heavy tap

// Notification feedback
$vibration->notification('success');
$vibration->notification('warning');
$vibration->notification('error');

// Selection feedback (subtle tick)
$vibration->selection();
```

---

## Geolocation Plugin

Access device GPS and location services.

### Get Current Position

```php
use Mucan54\TauriPhp\Plugins\Geolocation\Geolocation;

$geolocation = new Geolocation();

// Get current position
$position = $geolocation->getCurrentPosition([
    'enableHighAccuracy' => true,
    'timeout' => 10000,
]);

// Access coordinates
echo "Latitude: {$position->latitude}";
echo "Longitude: {$position->longitude}";
echo "Accuracy: {$position->accuracy} meters";

// Get Google Maps URL
echo $position->toGoogleMapsUrl();
```

### Watch Position Changes

```php
// Start watching
$watchId = $geolocation->watchPosition(
    ['enableHighAccuracy' => true],
    function($position) {
        Log::info("New position: {$position->latitude}, {$position->longitude}");
    }
);

// Stop watching
$geolocation->clearWatch($watchId);
```

### Position Methods

```php
// Calculate distance between two positions
$distance = $position1->distanceTo($position2); // meters

// Get as array
$coords = $position->toArray();

// Get as string
$coordsString = $position->toString(); // "lat, lng"
```

### Location Services

```php
// Check if enabled
if ($geolocation->isLocationEnabled()) {
    $position = $geolocation->getCurrentPosition();
} else {
    // Open settings
    $geolocation->openLocationSettings();
}
```

---

## Storage Plugin

Persistent key-value storage for mobile apps.

### Basic Usage

```php
use Mucan54\TauriPhp\Plugins\Storage\Storage;

$storage = new Storage();

// Store data
$storage->set('user_id', 123);
$storage->set('settings', ['theme' => 'dark', 'lang' => 'en']);

// Retrieve data
$userId = $storage->get('user_id');
$settings = $storage->get('settings', []); // with default

// Remove data
$storage->remove('user_id');

// Clear all
$storage->clear();
```

### Batch Operations

```php
// Set multiple values
$storage->setMultiple([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
]);

// Get multiple values
$data = $storage->getMultiple(['name', 'email', 'age']);

// Remove multiple
$storage->removeMultiple(['name', 'email']);
```

### Storage Inspection

```php
// Get all keys
$keys = $storage->keys();

// Check if key exists
if ($storage->has('user_id')) {
    // ...
}

// Get number of items
$count = $storage->length();
```

---

## Permissions

All plugins support permission checking and requesting:

```php
// Check permissions
$permissions = $plugin->checkPermissions();

// Request permissions
$plugin->requestPermissions();
```

Permission response format:
```php
[
    'camera' => 'granted',      // granted, denied, prompt
    'photos' => 'prompt',
]
```

---

## Error Handling

All plugin methods throw `TauriPhpException` on errors:

```php
use Mucan54\TauriPhp\Exceptions\TauriPhpException;

try {
    $photo = $camera->takePhoto();
} catch (TauriPhpException $e) {
    Log::error("Camera error: " . $e->getMessage());
    return response()->json(['error' => 'Camera not available'], 500);
}
```

---

## Frontend Integration

To use these plugins, you need to bridge PHP with the Tauri JavaScript API. Add this to your frontend:

```javascript
import { invoke } from '@tauri-apps/api/core';

// Listen for plugin commands from PHP
window.addEventListener('tauri-plugin-command', async (event) => {
    const { plugin, command, args } = event.detail;

    try {
        const result = await invoke(`plugin:${plugin}|${command}`, args);
        // Send result back to PHP via session or API
        await fetch('/api/tauri/plugin-response', {
            method: 'POST',
            body: JSON.stringify({ plugin, command, result })
        });
    } catch (error) {
        console.error(`Plugin ${plugin} command ${command} failed:`, error);
    }
});
```

---

## Platform Support

| Plugin | iOS | Android |
|--------|-----|---------|
| Camera | ✅ | ✅ |
| Notification | ✅ | ✅ |
| Vibration | ✅ | ✅ |
| Geolocation | ✅ | ✅ |
| Storage | ✅ | ✅ |

---

## Next Steps

1. See [Tauri Plugin Development](https://v2.tauri.app/develop/plugins/) for creating custom plugins
2. Check [iOS Setup Guide](./IOS-BUILD-QUICKSTART.md) for iOS-specific configuration
3. Review [tauri.conf.json](./ICONS_AND_CONFIG.md) configuration examples

---

## Contributing

To add a new plugin:

1. Create a new directory in `src/Plugins/YourPlugin`
2. Create the main plugin class extending `Plugin`
3. Add Rust/Swift/Kotlin implementations in `stubs/tauri/plugins/yourplugin`
4. Document usage in this file

See existing plugins for reference.

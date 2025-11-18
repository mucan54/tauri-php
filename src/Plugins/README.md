# Tauri-PHP Mobile Plugins

This directory contains PHP wrappers for Tauri mobile plugins.

## Quick Start

```php
use Mucan54\TauriPhp\Plugins\Camera\Camera;
use Mucan54\TauriPhp\Plugins\Notification\Notification;
use Mucan54\TauriPhp\Plugins\Vibration\Vibration;
use Mucan54\TauriPhp\Plugins\Geolocation\Geolocation;
use Mucan54\TauriPhp\Plugins\Storage\Storage;

// Camera
$camera = new Camera();
$photo = $camera->takePhoto(['quality' => 90]);

// Notifications
$notification = new Notification();
$notification->schedule([
    'title' => 'Hello',
    'body' => 'Test notification'
]);

// Vibration
$vibration = new Vibration();
$vibration->impact('medium');

// Geolocation
$geolocation = new Geolocation();
$position = $geolocation->getCurrentPosition();

// Storage
$storage = new Storage();
$storage->set('key', 'value');
$value = $storage->get('key');
```

## Available Plugins

| Plugin | Description | Class |
|--------|-------------|-------|
| Camera | Take photos, access gallery | `Mucan54\TauriPhp\Plugins\Camera\Camera` |
| Notification | Local push notifications | `Mucan54\TauriPhp\Plugins\Notification\Notification` |
| Vibration | Haptic feedback | `Mucan54\TauriPhp\Plugins\Vibration\Vibration` |
| Geolocation | GPS and location | `Mucan54\TauriPhp\Plugins\Geolocation\Geolocation` |
| Storage | Persistent storage | `Mucan54\TauriPhp\Plugins\Storage\Storage` |

## Documentation

Full documentation available at: [docs/MOBILE_PLUGINS.md](../../docs/MOBILE_PLUGINS.md)

## Architecture

```
Tauri Mobile App
├── Frontend (JavaScript/TypeScript)
│   ├── Calls Tauri Plugin API
│   └── Sends results to Laravel
├── Tauri Rust Core
│   ├── Receives JS commands
│   └── Calls native code (Swift/Kotlin)
└── Laravel PHP Backend
    ├── Plugin Classes (this directory)
    └── Receives data from frontend
```

## Implementation Status

| Plugin | PHP Class | iOS (Swift) | Android (Kotlin) | Documentation |
|--------|-----------|-------------|------------------|---------------|
| Camera | ✅ | Template | Template | ✅ |
| Notification | ✅ | Template | Template | ✅ |
| Vibration | ✅ | Template | Template | ✅ |
| Geolocation | ✅ | Template | Template | ✅ |
| Storage | ✅ | Template | Template | ✅ |

**Note**: The native implementations (Swift/Kotlin) are provided as templates in `stubs/tauri/plugins/`. You'll need to implement the actual native functionality based on your requirements.

## Adding Custom Plugins

1. Create a new directory: `src/Plugins/YourPlugin/`
2. Create the main class extending `Plugin`
3. Add native implementation stubs
4. Update documentation

Example:
```php
<?php

namespace Mucan54\TauriPhp\Plugins\YourPlugin;

use Mucan54\TauriPhp\Plugins\Plugin;

class YourPlugin extends Plugin
{
    protected $pluginName = 'your-plugin';

    public function yourMethod(array $options): mixed
    {
        return $this->invoke('yourMethod', $options);
    }
}
```

## License

Same as the parent package.

# Configuring Laravel for iOS

This guide explains how to configure Laravel to work within iOS filesystem sandboxing when running as an embedded application.

## iOS Filesystem Constraints

iOS apps run in a **sandbox** with restricted filesystem access. Your Laravel app can only access specific directories:

```
App Sandbox/
├── Documents/          # User-generated content, backed up by iCloud
├── Library/
│   ├── Application Support/  # App-specific files
│   └── Caches/              # Temporary cache files
└── tmp/               # Temporary files (deleted by system)
```

## Laravel Directory Structure on iOS

When your Laravel app runs on iOS, directories are mapped as follows:

```
Documents/laravel/
├── storage/
│   ├── app/
│   ├── framework/
│   │   ├── cache/
│   │   ├── sessions/
│   │   └── views/
│   └── logs/
├── database/
│   └── database.sqlite
└── bootstrap/cache/
```

## Configuration Changes

### 1. Update `.env` for Mobile

Create a `.env.mobile` file for iOS-specific configuration:

```bash
APP_ENV=mobile
APP_DEBUG=false

# Database - Use SQLite for iOS
DB_CONNECTION=sqlite
DB_DATABASE=/var/mobile/Containers/Data/Application/{{APP_UUID}}/Documents/laravel/database/database.sqlite

# Cache - Use file driver
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=single
LOG_LEVEL=debug

# Disable external services
MAIL_MAILER=log
BROADCAST_DRIVER=log
```

### 2. Runtime Path Configuration

The Tauri Rust code will set environment variables at runtime:

```rust
// In lib.rs
#[cfg(target_os = "ios")]
{
    use std::env;

    // Get iOS Documents directory
    let docs_dir = app_handle
        .path_resolver()
        .app_data_dir()
        .expect("Failed to get app data dir");

    let laravel_root = docs_dir.join("laravel");

    // Set Laravel paths via environment
    env::set_var("LARAVEL_STORAGE_PATH", laravel_root.join("storage").to_str().unwrap());
    env::set_var("LARAVEL_BOOTSTRAP_CACHE", laravel_root.join("bootstrap/cache").to_str().unwrap());
    env::set_var("LARAVEL_DATABASE_PATH", laravel_root.join("database").to_str().unwrap());
}
```

### 3. Update Laravel Bootstrap

Modify `bootstrap/app.php` to respect iOS paths:

```php
<?php

use Illuminate\Foundation\Application;

$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Override storage path for mobile
if (isset($_ENV['LARAVEL_STORAGE_PATH'])) {
    $app->useStoragePath($_ENV['LARAVEL_STORAGE_PATH']);
}

// Override bootstrap cache path
if (isset($_ENV['LARAVEL_BOOTSTRAP_CACHE'])) {
    $app->bootstrapPath($_ENV['LARAVEL_BOOTSTRAP_CACHE']);
}

return $app;
```

### 4. Database Configuration

Update `config/database.php` for iOS SQLite:

```php
'sqlite' => [
    'driver' => 'sqlite',
    'url' => env('DATABASE_URL'),
    'database' => env('DB_DATABASE',
        // Use runtime path if set, otherwise default
        $_ENV['LARAVEL_DATABASE_PATH']
            ? $_ENV['LARAVEL_DATABASE_PATH'] . '/database.sqlite'
            : database_path('database.sqlite')
    ),
    'prefix' => '',
    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
],
```

## First Launch Setup

On iOS app first launch, you need to:

1. **Create directory structure**
2. **Copy Laravel files**
3. **Run migrations**
4. **Cache configuration**

### Initialization Command

Create `app/Console/Commands/SetupMobile.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class SetupMobile extends Command
{
    protected $signature = 'mobile:setup';
    protected $description = 'Setup Laravel for iOS/Android first launch';

    public function handle()
    {
        $this->info('Setting up Laravel for mobile...');

        // Create directories
        $this->createDirectories();

        // Run migrations
        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);

        // Cache config
        $this->info('Caching configuration...');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->info('Mobile setup complete!');
    }

    protected function createDirectories()
    {
        $directories = [
            storage_path('app'),
            storage_path('app/public'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            database_path(),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("Created: {$directory}");
            }
        }
    }
}
```

### Call Setup on First Launch

Update Rust `lib.rs` to run setup on first launch:

```rust
#[cfg(target_os = "ios")]
{
    // Check if already initialized
    let marker_file = docs_dir.join("laravel/.initialized");

    if !marker_file.exists() {
        // First launch - run setup
        let setup_result = app
            .shell()
            .sidecar(binary_name)
            .args(&["artisan", "mobile:setup"])
            .output()
            .await?;

        // Create marker file
        std::fs::write(&marker_file, "1")?;
    }

    // Start server
    let _server = app
        .shell()
        .sidecar(binary_name)
        .args(&["-S", "127.0.0.1:8080", "-t", "public"])
        .spawn()?;
}
```

## File Upload Handling

iOS has restrictions on file access. For file uploads:

### Using iOS Document Picker

```javascript
// In your Tauri frontend
import { open } from '@tauri-apps/api/dialog';

async function selectFile() {
    const selected = await open({
        multiple: false,
        filters: [{
            name: 'Image',
            extensions: ['png', 'jpeg', 'jpg']
        }]
    });

    // selected contains the iOS file path
    // Upload to Laravel as usual
}
```

### Laravel Upload Handling

```php
// In your controller
public function upload(Request $request)
{
    $file = $request->file('upload');

    // Store in iOS-accessible location
    $path = $file->store('uploads', 'public');

    return response()->json(['path' => $path]);
}
```

## Performance Optimization

### 1. Precompile Everything

Before bundling the iOS app, cache everything:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 2. Use OPcache Alternative

Since OPcache doesn't work with static PHP, consider:

```php
// In config/app.php
'providers' => [
    // Remove OPcache-dependent providers if any
],
```

### 3. Optimize Autoloader

```bash
composer install --optimize-autoloader --no-dev
```

## Testing on iOS Simulator

### 1. Build and Run

```bash
# Build PHP binary
cd scripts
./build-php-ios.sh

# Copy to project
cp ../binaries/php-iphonesimulator-arm64 your-project/binaries/

# Run on simulator
cd your-project
php artisan tauri:mobile-dev ios --emulator
```

### 2. Check Logs

iOS logs will show Laravel startup:

```
[INFO] Laravel application starting...
[INFO] Storage path: /var/mobile/Containers/Data/Application/.../Documents/laravel/storage
[INFO] Database: /var/mobile/Containers/Data/Application/.../Documents/laravel/database/database.sqlite
[INFO] Server listening on 127.0.0.1:8080
```

## Common Issues

### Issue 1: "Permission Denied" on Storage

**Cause**: iOS sandbox doesn't have write permission
**Solution**: Ensure all paths are within Documents directory

```php
// ❌ Wrong - tries to write outside sandbox
storage_path('/var/www/storage')

// ✅ Correct - uses iOS Documents
$_ENV['LARAVEL_STORAGE_PATH'] . '/app'
```

### Issue 2: Database Not Found

**Cause**: Database path not set correctly
**Solution**: Verify environment variable is set:

```rust
env::set_var("LARAVEL_DATABASE_PATH", laravel_root.join("database").to_str().unwrap());
```

### Issue 3: Views Not Rendering

**Cause**: View cache not created
**Solution**: Run `php artisan view:cache` before bundling

## Production Checklist

Before App Store submission:

- [ ] All caching enabled (`config:cache`, `route:cache`, `view:cache`)
- [ ] Debug mode disabled (`APP_DEBUG=false`)
- [ ] Logs set to appropriate level
- [ ] Database migrations tested
- [ ] File permissions verified
- [ ] No external network dependencies (if offline app)
- [ ] Icon assets included
- [ ] Privacy manifest updated

## Example App Structure

```
your-laravel-ios-app/
├── app/                    # Laravel app code
├── public/                 # Laravel public directory
├── resources/              # Views, assets
├── src-tauri/              # Tauri Rust code
│   └── src/
│       └── lib.rs         # iOS PHP spawning logic
├── binaries/
│   ├── php-iphoneos-arm64
│   └── php-iphonesimulator-arm64
├── .env.mobile            # iOS-specific config
└── tauri.conf.json        # Bundles PHP binaries
```

## Next Steps

After configuring Laravel for iOS:

1. Test all routes and controllers
2. Verify database operations
3. Test file uploads/downloads
4. Check performance metrics
5. Submit to TestFlight for beta testing
6. Gather feedback and optimize

## Support

For iOS-specific Laravel issues:
- Check PHP binary compatibility with `./binaries/php-iphonesimulator-arm64 -v`
- Verify storage permissions in iOS sandbox
- Review Xcode console for detailed logs
- Test on both simulator and real device

## Credits

This approach is inspired by:
- PocketMine-MP mobile PHP implementation
- Capacitor/Cordova iOS filesystem handling
- React Native iOS integration patterns

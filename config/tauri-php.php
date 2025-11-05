<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FrankenPHP Settings
    |--------------------------------------------------------------------------
    |
    | Configure the FrankenPHP binary that will be embedded in your Tauri
    | application. FrankenPHP provides the PHP runtime without requiring
    | users to have PHP installed on their systems.
    |
    */

    'frankenphp' => [
        'version' => env('TAURI_FRANKENPHP_VERSION', 'latest'),
        'php_version' => env('TAURI_PHP_VERSION', '8.3'),
        'php_extensions' => env('TAURI_PHP_EXTENSIONS', 'opcache,pdo_sqlite,mbstring,openssl,tokenizer,xml,ctype,json,bcmath,fileinfo'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Targets
    |--------------------------------------------------------------------------
    |
    | Define the target triples for different platforms. These are used
    | when building FrankenPHP binaries and Tauri applications for
    | cross-platform distribution.
    |
    */

    'platforms' => [
        'linux-x64' => 'x86_64-unknown-linux-gnu',
        'linux-arm64' => 'aarch64-unknown-linux-gnu',
        'macos-x64' => 'x86_64-apple-darwin',
        'macos-arm64' => 'aarch64-apple-darwin',
        'windows-x64' => 'x86_64-pc-windows-msvc',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Information
    |--------------------------------------------------------------------------
    |
    | Default application information used when initializing a new Tauri
    | project. These can be overridden during initialization or via the
    | .env.tauri file.
    |
    */

    'app' => [
        'name' => env('TAURI_APP_NAME', 'My Desktop App'),
        'identifier' => env('TAURI_APP_IDENTIFIER', 'com.example.myapp'),
        'version' => env('TAURI_APP_VERSION', '0.1.0'),
        'window' => [
            'title' => env('TAURI_WINDOW_TITLE', 'My Desktop App'),
            'width' => env('TAURI_WINDOW_WIDTH', 1200),
            'height' => env('TAURI_WINDOW_HEIGHT', 800),
            'resizable' => env('TAURI_WINDOW_RESIZABLE', true),
            'fullscreen' => env('TAURI_WINDOW_FULLSCREEN', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Server
    |--------------------------------------------------------------------------
    |
    | Configuration for the development server used when running the
    | tauri:dev command. The server will be started on this host and port.
    |
    */

    'dev' => [
        'host' => env('TAURI_DEV_HOST', '127.0.0.1'),
        'port' => env('TAURI_DEV_PORT', 8080),
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Protection
    |--------------------------------------------------------------------------
    |
    | Configure code obfuscation settings to protect your PHP source code
    | when distributing your application.
    |
    */

    'obfuscation' => [
        'enabled' => env('TAURI_OBFUSCATE_CODE', false),
        'tool' => env('TAURI_OBFUSCATE_TOOL', 'yakpro-po'),
        'exclude_paths' => [
            'vendor',
            'storage',
            'bootstrap/cache',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Build Paths
    |--------------------------------------------------------------------------
    |
    | Directories used during the build process. These paths are relative
    | to your Laravel application's base path.
    |
    */

    'paths' => [
        'tauri_dir' => env('TAURI_DIR', 'src-tauri'),
        'frontend_dir' => env('TAURI_FRONTEND_DIR', 'desktop-frontend'),
        'binaries_dir' => env('TAURI_BINARIES_DIR', 'binaries'),
        'temp_dir' => env('TAURI_TEMP_DIR', 'tauri-temp'),
        'build_output_dir' => env('TAURI_BUILD_OUTPUT_DIR', 'tauri-builds'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Build Settings
    |--------------------------------------------------------------------------
    |
    | General build configuration options.
    |
    */

    'build' => [
        'debug' => env('TAURI_BUILD_DEBUG', false),
        'parallel' => env('TAURI_BUILD_PARALLEL', true),
        'cleanup_after_build' => env('TAURI_CLEANUP_AFTER_BUILD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Signing (Optional)
    |--------------------------------------------------------------------------
    |
    | Configuration for signing your application binaries. This is required
    | for distribution on macOS and recommended for Windows.
    |
    */

    'signing' => [
        'windows' => [
            'enabled' => env('TAURI_SIGN_WINDOWS', false),
            'certificate_thumbprint' => env('TAURI_WINDOWS_CERTIFICATE_THUMBPRINT', ''),
        ],
        'macos' => [
            'enabled' => env('TAURI_SIGN_MACOS', false),
            'signing_identity' => env('TAURI_MACOS_SIGNING_IDENTITY', ''),
        ],
    ],

];

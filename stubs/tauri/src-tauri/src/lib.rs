#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use tauri::Manager;
use tauri_plugin_shell::ShellExt;
use std::sync::Mutex;
use std::path::PathBuf;

struct AppState {
    server_running: Mutex<bool>,
}

#[cfg(any(target_os = "ios", target_os = "android"))]
fn setup_mobile_directories(base_path: &PathBuf) -> Result<(), String> {
    use std::fs;

    let directories = vec![
        base_path.join("storage/app/public"),
        base_path.join("storage/framework/cache"),
        base_path.join("storage/framework/sessions"),
        base_path.join("storage/framework/views"),
        base_path.join("storage/logs"),
        base_path.join("database"),
        base_path.join("bootstrap/cache"),
    ];

    for dir in directories {
        fs::create_dir_all(&dir)
            .map_err(|e| format!("Failed to create directory {:?}: {}", dir, e))?;
    }

    Ok(())
}

#[cfg(any(target_os = "ios", target_os = "android"))]
fn is_first_launch(base_path: &PathBuf) -> bool {
    !base_path.join(".initialized").exists()
}

#[cfg(any(target_os = "ios", target_os = "android"))]
fn mark_initialized(base_path: &PathBuf) -> Result<(), String> {
    use std::fs;
    fs::write(base_path.join(".initialized"), "1")
        .map_err(|e| format!("Failed to mark as initialized: {}", e))
}

#[tauri::command]
async fn start_laravel_server(
    app: tauri::AppHandle,
    state: tauri::State<'_, AppState>,
) -> Result<String, String> {
    let mut running = state.server_running.lock().unwrap();

    if *running {
        return Ok("Server is already running".to_string());
    }

    // Platform-specific PHP binary spawning
    #[cfg(target_os = "ios")]
    {
        // iOS: Use embedded static PHP binary
        let binary_name = if cfg!(target_arch = "aarch64") {
            // Note: Tauri will automatically select the correct binary based on build target
            // For simulator builds: php-iphonesimulator-arm64
            // For device builds: php-iphoneos-arm64
            "php-iphoneos-arm64"
        } else {
            "php-iphonesimulator-arm64"
        };

        // Get iOS app data directory (Documents folder)
        let app_data_dir = app
            .path()
            .app_data_dir()
            .map_err(|e| format!("Failed to get app data directory: {}", e))?;

        let laravel_root = app_data_dir.join("laravel");

        // First launch: setup directories
        if is_first_launch(&laravel_root) {
            setup_mobile_directories(&laravel_root)?;
            mark_initialized(&laravel_root)?;
        }

        // Set Laravel environment variables for iOS paths
        std::env::set_var("LARAVEL_STORAGE_PATH", laravel_root.join("storage"));
        std::env::set_var("LARAVEL_BOOTSTRAP_CACHE", laravel_root.join("bootstrap/cache"));
        std::env::set_var("LARAVEL_DATABASE_PATH", laravel_root.join("database"));
        std::env::set_var("DB_DATABASE", laravel_root.join("database/database.sqlite"));

        // Start PHP built-in server
        let _sidecar = app
            .shell()
            .sidecar(binary_name)
            .map_err(|e| format!("Failed to spawn PHP binary: {}", e))?
            .args(&["-S", "127.0.0.1:8080", "-t", "public"])
            .current_dir(&laravel_root)
            .spawn()
            .map_err(|e| format!("Failed to start PHP server: {}", e))?;
    }

    #[cfg(target_os = "android")]
    {
        // Android: Use embedded static PHP binary
        // Get Android app data directory
        let app_data_dir = app
            .path()
            .app_data_dir()
            .map_err(|e| format!("Failed to get app data directory: {}", e))?;

        let laravel_root = app_data_dir.join("laravel");

        // First launch: setup directories
        if is_first_launch(&laravel_root) {
            setup_mobile_directories(&laravel_root)?;
            mark_initialized(&laravel_root)?;
        }

        // Set Laravel environment variables for Android paths
        std::env::set_var("LARAVEL_STORAGE_PATH", laravel_root.join("storage"));
        std::env::set_var("LARAVEL_BOOTSTRAP_CACHE", laravel_root.join("bootstrap/cache"));
        std::env::set_var("LARAVEL_DATABASE_PATH", laravel_root.join("database"));
        std::env::set_var("DB_DATABASE", laravel_root.join("database/database.sqlite"));

        // Start PHP built-in server
        let _sidecar = app
            .shell()
            .sidecar("php-android-aarch64")
            .map_err(|e| format!("Failed to spawn PHP binary: {}", e))?
            .args(&["-S", "127.0.0.1:8080", "-t", "public"])
            .current_dir(&laravel_root)
            .spawn()
            .map_err(|e| format!("Failed to start PHP server: {}", e))?;
    }

    #[cfg(not(any(target_os = "ios", target_os = "android")))]
    {
        // Desktop: Use FrankenPHP sidecar
        let _sidecar = app
            .shell()
            .sidecar("frankenphp")
            .map_err(|e| format!("Failed to spawn sidecar: {}", e))?
            .args(&["php-server", "--listen", "127.0.0.1:8080"])
            .spawn()
            .map_err(|e| format!("Failed to start server: {}", e))?;
    }

    *running = true;
    drop(running);

    Ok("Server started successfully".to_string())
}

#[tauri::command]
fn get_server_status(state: tauri::State<'_, AppState>) -> bool {
    *state.server_running.lock().unwrap()
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .manage(AppState {
            server_running: Mutex::new(false),
        })
        .invoke_handler(tauri::generate_handler![
            start_laravel_server,
            get_server_status
        ])
        .setup(|app| {
            // Auto-start Laravel server
            let app_handle = app.handle().clone();
            tauri::async_runtime::spawn(async move {
                // Give the app a moment to initialize
                tokio::time::sleep(tokio::time::Duration::from_millis(500)).await;

                // Start the server
                let state = app_handle.state::<AppState>();
                let _ = start_laravel_server(app_handle.clone(), state).await;
            });

            Ok(())
        })
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}

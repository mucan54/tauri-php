#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use tauri::Manager;
use tauri_plugin_shell::ShellExt;
use std::sync::Mutex;

struct AppState {
    server_running: Mutex<bool>,
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

    // Start FrankenPHP sidecar
    let sidecar = app
        .shell()
        .sidecar("frankenphp")
        .map_err(|e| format!("Failed to spawn sidecar: {}", e))?
        .args(&["php-server", "--listen", "127.0.0.1:8080"])
        .spawn()
        .map_err(|e| format!("Failed to start server: {}", e))?;

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

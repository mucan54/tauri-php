// Prevents additional console window on Windows in release builds
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

fn main() {
    {{APP_NAME_SANITIZED}}_lib::run()
}

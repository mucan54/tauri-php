use tauri::{
    plugin::{Builder, TauriPlugin},
    Runtime, Manager, AppHandle,
};
use serde::{Deserialize, Serialize};

#[derive(Debug, Deserialize)]
#[serde(rename_all = "camelCase")]
pub struct TakePhotoArgs {
    pub quality: Option<u8>,
    pub allow_editing: Option<bool>,
    pub save_to_gallery: Option<bool>,
    pub result_type: Option<String>,
    pub width: Option<u32>,
    pub height: Option<u32>,
}

#[derive(Debug, Serialize)]
#[serde(rename_all = "camelCase")]
pub struct PhotoResult {
    pub path: String,
    pub data: Option<String>,
    pub format: String,
    pub width: Option<u32>,
    pub height: Option<u32>,
}

#[tauri::command]
async fn take_photo<R: Runtime>(
    _app: AppHandle<R>,
    args: TakePhotoArgs,
) -> Result<PhotoResult, String> {
    #[cfg(mobile)]
    {
        mobile::take_photo(args).await
    }

    #[cfg(not(mobile))]
    {
        Err("Camera plugin is only available on mobile platforms".into())
    }
}

#[tauri::command]
async fn pick_photo<R: Runtime>(
    _app: AppHandle<R>,
    args: TakePhotoArgs,
) -> Result<PhotoResult, String> {
    #[cfg(mobile)]
    {
        mobile::pick_photo(args).await
    }

    #[cfg(not(mobile))]
    {
        Err("Camera plugin is only available on mobile platforms".into())
    }
}

#[tauri::command]
async fn pick_multiple_photos<R: Runtime>(
    _app: AppHandle<R>,
    limit: Option<u32>,
) -> Result<Vec<PhotoResult>, String> {
    #[cfg(mobile)]
    {
        mobile::pick_multiple_photos(limit).await
    }

    #[cfg(not(mobile))]
    {
        Err("Camera plugin is only available on mobile platforms".into())
    }
}

#[cfg(mobile)]
mod mobile {
    use super::*;
    use tauri_plugin_camera::Camera;

    pub async fn take_photo(args: TakePhotoArgs) -> Result<PhotoResult, String> {
        // This will call the native mobile implementation
        Camera::take_photo(
            args.quality.unwrap_or(90),
            args.allow_editing.unwrap_or(false),
            args.save_to_gallery.unwrap_or(false),
        )
        .await
        .map_err(|e| e.to_string())
    }

    pub async fn pick_photo(args: TakePhotoArgs) -> Result<PhotoResult, String> {
        Camera::pick_photo(
            args.quality.unwrap_or(90),
            args.allow_editing.unwrap_or(false),
        )
        .await
        .map_err(|e| e.to_string())
    }

    pub async fn pick_multiple_photos(limit: Option<u32>) -> Result<Vec<PhotoResult>, String> {
        Camera::pick_multiple_photos(limit.unwrap_or(10))
            .await
            .map_err(|e| e.to_string())
    }
}

pub fn init<R: Runtime>() -> TauriPlugin<R> {
    Builder::new("camera")
        .invoke_handler(tauri::generate_handler![
            take_photo,
            pick_photo,
            pick_multiple_photos,
        ])
        .build()
}

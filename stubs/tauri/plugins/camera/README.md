# Camera Plugin Stub

This directory contains template code for implementing the Camera plugin in Tauri.

## Implementation Guide

### 1. iOS (Swift)

Create `ios/Sources/CameraPlugin.swift`:

```swift
import UIKit
import Tauri
import WebKit

class CameraPlugin: Plugin {
    @objc public func takePhoto(_ invoke: Invoke) throws {
        let args = try invoke.parseArgs(TakePhotoArgs.self)

        // Implementation using UIImagePickerController
        DispatchQueue.main.async {
            let picker = UIImagePickerController()
            picker.sourceType = .camera
            picker.delegate = self
            // ... implement camera logic

            invoke.resolve(["path": "/path/to/photo.jpg"])
        }
    }

    @objc public func pickPhoto(_ invoke: Invoke) throws {
        let args = try invoke.parseArgs(PickPhotoArgs.self)

        DispatchQueue.main.async {
            let picker = UIImagePickerController()
            picker.sourceType = .photoLibrary
            // ... implement gallery picker

            invoke.resolve(["path": "/path/to/photo.jpg"])
        }
    }

    @objc public override func checkPermissions(_ invoke: Invoke) {
        let cameraStatus = AVCaptureDevice.authorizationStatus(for: .video)
        let photoStatus = PHPhotoLibrary.authorizationStatus()

        invoke.resolve([
            "camera": permissionString(cameraStatus),
            "photos": permissionString(photoStatus)
        ])
    }

    @objc public override func requestPermissions(_ invoke: Invoke) {
        AVCaptureDevice.requestAccess(for: .video) { granted in
            PHPhotoLibrary.requestAuthorization { status in
                invoke.resolve([
                    "camera": granted ? "granted" : "denied",
                    "photos": self.permissionString(status)
                ])
            }
        }
    }
}

class TakePhotoArgs: Decodable {
    var quality: Int?
    var allowEditing: Bool?
    var saveToGallery: Bool?
}

class PickPhotoArgs: Decodable {
    var quality: Int?
    var allowEditing: Bool?
}
```

### 2. Android (Kotlin)

Create `android/src/main/java/CameraPlugin.kt`:

```kotlin
package com.plugin.camera

import android.Manifest
import app.tauri.annotation.Command
import app.tauri.annotation.InvokeArg
import app.tauri.annotation.Permission
import app.tauri.annotation.TauriPlugin
import app.tauri.plugin.Invoke
import app.tauri.plugin.JSObject
import app.tauri.plugin.Plugin

@TauriPlugin(
    permissions = [
        Permission(strings = [Manifest.permission.CAMERA], alias = "camera"),
        Permission(strings = [Manifest.permission.READ_EXTERNAL_STORAGE], alias = "photos")
    ]
)
class CameraPlugin(private val activity: Activity) : Plugin(activity) {

    @Command
    fun takePhoto(invoke: Invoke) {
        val args = invoke.parseArgs(TakePhotoArgs::class.java)

        // Implementation using Android Camera APIs
        val intent = Intent(MediaStore.ACTION_IMAGE_CAPTURE)
        activity.startActivityForResult(intent, CAMERA_REQUEST_CODE)

        val ret = JSObject()
        ret.put("path", "/path/to/photo.jpg")
        invoke.resolve(ret)
    }

    @Command
    fun pickPhoto(invoke: Invoke) {
        val args = invoke.parseArgs(PickPhotoArgs::class.java)

        val intent = Intent(Intent.ACTION_PICK)
        intent.type = "image/*"
        activity.startActivityForResult(intent, GALLERY_REQUEST_CODE)

        val ret = JSObject()
        ret.put("path", "/path/to/photo.jpg")
        invoke.resolve(ret)
    }

    companion object {
        const val CAMERA_REQUEST_CODE = 100
        const val GALLERY_REQUEST_CODE = 101
    }
}

@InvokeArg
internal class TakePhotoArgs {
    var quality: Int = 90
    var allowEditing: Boolean = false
    var saveToGallery: Boolean = false
}

@InvokeArg
internal class PickPhotoArgs {
    var quality: Int = 90
    var allowEditing: Boolean = false
}
```

### 3. Rust Bridge

Create `src/mobile.rs`:

```rust
use tauri::{
    plugin::{Builder, TauriPlugin},
    Manager, Runtime,
};

#[cfg(mobile)]
use tauri::plugin::PluginApi;

pub fn init<R: Runtime>() -> TauriPlugin<R> {
    Builder::new("camera")
        .invoke_handler(tauri::generate_handler![
            take_photo,
            pick_photo,
            pick_multiple_photos,
        ])
        .build()
}

#[tauri::command]
async fn take_photo<R: Runtime>(
    app: tauri::AppHandle<R>,
    options: serde_json::Value,
) -> Result<serde_json::Value, String> {
    app.camera()
        .take_photo(options)
        .map_err(|e| e.to_string())
}

#[tauri::command]
async fn pick_photo<R: Runtime>(
    app: tauri::AppHandle<R>,
    options: serde_json::Value,
) -> Result<serde_json::Value, String> {
    app.camera()
        .pick_photo(options)
        .map_err(|e| e.to_string())
}
```

## Usage from JavaScript

```javascript
import { invoke } from '@tauri-apps/api/core';

// Take a photo
const result = await invoke('plugin:camera|take_photo', {
    quality: 90,
    allowEditing: true
});

console.log('Photo path:', result.path);
```

## Integration with PHP

The JavaScript layer communicates with PHP via the bridge described in [MOBILE_PLUGINS.md](../../../docs/MOBILE_PLUGINS.md#frontend-integration).

## References

- [Tauri Plugin Development](https://v2.tauri.app/develop/plugins/)
- [iOS Camera APIs](https://developer.apple.com/documentation/uikit/uiimagepickercontroller)
- [Android Camera APIs](https://developer.android.com/training/camera)

package com.tauri.plugin.camera

import android.Manifest
import android.app.Activity
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.net.Uri
import android.os.Build
import android.provider.MediaStore
import android.util.Base64
import androidx.activity.result.ActivityResult
import androidx.activity.result.ActivityResultLauncher
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import app.tauri.annotation.Command
import app.tauri.annotation.TauriPlugin
import app.tauri.plugin.Invoke
import app.tauri.plugin.JSObject
import app.tauri.plugin.Plugin
import java.io.ByteArrayOutputStream
import java.io.File
import java.io.FileOutputStream
import java.io.IOException
import java.util.UUID

/**
 * Camera Plugin for Android
 *
 * Provides access to the device camera and photo gallery.
 * Supports taking photos, picking from gallery, and multiple photo selection.
 */
@TauriPlugin
class CameraPlugin : Plugin() {
    private var currentInvoke: Invoke? = null
    private var photoFile: File? = null
    private var resultType: String = "uri"
    private var quality: Int = 90
    private var maxWidth: Int? = null
    private var maxHeight: Int? = null
    private var preserveAspectRatio: Boolean = true

    // Activity result launchers
    private lateinit var takePictureLauncher: ActivityResultLauncher<Intent>
    private lateinit var pickPhotoLauncher: ActivityResultLauncher<Intent>
    private lateinit var pickMultiplePhotosLauncher: ActivityResultLauncher<Intent>
    private lateinit var requestCameraPermissionLauncher: ActivityResultLauncher<String>
    private lateinit var requestStoragePermissionLauncher: ActivityResultLauncher<String>

    override fun load() {
        super.load()

        // Register activity result launchers
        takePictureLauncher = activity.registerForActivityResult(
            ActivityResultContracts.StartActivityForResult()
        ) { result -> handleTakePictureResult(result) }

        pickPhotoLauncher = activity.registerForActivityResult(
            ActivityResultContracts.StartActivityForResult()
        ) { result -> handlePickPhotoResult(result) }

        pickMultiplePhotosLauncher = activity.registerForActivityResult(
            ActivityResultContracts.StartActivityForResult()
        ) { result -> handlePickMultiplePhotosResult(result) }

        requestCameraPermissionLauncher = activity.registerForActivityResult(
            ActivityResultContracts.RequestPermission()
        ) { granted ->
            if (granted) {
                launchCamera()
            } else {
                currentInvoke?.reject("Camera permission denied")
                currentInvoke = null
            }
        }

        requestStoragePermissionLauncher = activity.registerForActivityResult(
            ActivityResultContracts.RequestPermission()
        ) { granted ->
            if (granted) {
                launchPhotoPicker(false)
            } else {
                currentInvoke?.reject("Storage permission denied")
                currentInvoke = null
            }
        }
    }

    // MARK: - Take Photo

    @Command
    fun takePhoto(invoke: Invoke) {
        this.currentInvoke = invoke

        // Parse options
        val args = invoke.parseArgs(TakePhotoArgs::class.java)
        this.resultType = args.resultType ?: "uri"
        this.quality = args.quality ?: 90
        this.maxWidth = args.width
        this.maxHeight = args.height
        this.preserveAspectRatio = args.preserveAspectRatio ?: true

        // Check camera permission
        if (ContextCompat.checkSelfPermission(
                activity,
                Manifest.permission.CAMERA
            ) != PackageManager.PERMISSION_GRANTED
        ) {
            requestCameraPermissionLauncher.launch(Manifest.permission.CAMERA)
            return
        }

        launchCamera()
    }

    private fun launchCamera() {
        val intent = Intent(MediaStore.ACTION_IMAGE_CAPTURE)

        // Create file to store the photo
        try {
            photoFile = createImageFile()
            val photoUri = FileProvider.getUriForFile(
                activity,
                "${activity.packageName}.fileprovider",
                photoFile!!
            )
            intent.putExtra(MediaStore.EXTRA_OUTPUT, photoUri)

            takePictureLauncher.launch(intent)
        } catch (e: IOException) {
            currentInvoke?.reject("Failed to create image file: ${e.message}")
            currentInvoke = null
        }
    }

    private fun handleTakePictureResult(result: ActivityResult) {
        if (result.resultCode == Activity.RESULT_OK) {
            photoFile?.let { file ->
                val bitmap = BitmapFactory.decodeFile(file.absolutePath)
                if (bitmap != null) {
                    val processedBitmap = processBitmap(bitmap)
                    val photoResult = bitmapToResult(processedBitmap, file)
                    currentInvoke?.resolve(photoResult)
                } else {
                    currentInvoke?.reject("Failed to decode image")
                }
            } ?: run {
                currentInvoke?.reject("Photo file is null")
            }
        } else {
            currentInvoke?.reject("User cancelled")
        }

        currentInvoke = null
        photoFile = null
    }

    // MARK: - Pick Photo

    @Command
    fun pickPhoto(invoke: Invoke) {
        this.currentInvoke = invoke

        // Parse options
        val args = invoke.parseArgs(PickPhotoArgs::class.java)
        this.resultType = args.resultType ?: "uri"
        this.quality = args.quality ?: 90
        this.maxWidth = args.width
        this.maxHeight = args.height
        this.preserveAspectRatio = args.preserveAspectRatio ?: true

        // Check storage permission (only needed for Android < 13)
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(
                    activity,
                    Manifest.permission.READ_EXTERNAL_STORAGE
                ) != PackageManager.PERMISSION_GRANTED
            ) {
                requestStoragePermissionLauncher.launch(Manifest.permission.READ_EXTERNAL_STORAGE)
                return
            }
        }

        launchPhotoPicker(false)
    }

    // MARK: - Pick Multiple Photos

    @Command
    fun pickMultiplePhotos(invoke: Invoke) {
        this.currentInvoke = invoke

        // Parse options
        val args = invoke.parseArgs(PickMultiplePhotosArgs::class.java)
        this.resultType = args.resultType ?: "uri"
        this.quality = args.quality ?: 90

        // Check storage permission (only needed for Android < 13)
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(
                    activity,
                    Manifest.permission.READ_EXTERNAL_STORAGE
                ) != PackageManager.PERMISSION_GRANTED
            ) {
                requestStoragePermissionLauncher.launch(Manifest.permission.READ_EXTERNAL_STORAGE)
                return
            }
        }

        launchPhotoPicker(true)
    }

    private fun launchPhotoPicker(multiple: Boolean) {
        val intent = Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI)
        intent.type = "image/*"

        if (multiple) {
            intent.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true)
            pickMultiplePhotosLauncher.launch(intent)
        } else {
            pickPhotoLauncher.launch(intent)
        }
    }

    private fun handlePickPhotoResult(result: ActivityResult) {
        if (result.resultCode == Activity.RESULT_OK) {
            result.data?.data?.let { uri ->
                val bitmap = uriToBitmap(uri)
                if (bitmap != null) {
                    val processedBitmap = processBitmap(bitmap)
                    val photoResult = bitmapToResult(processedBitmap, null)
                    currentInvoke?.resolve(photoResult)
                } else {
                    currentInvoke?.reject("Failed to decode image")
                }
            } ?: run {
                currentInvoke?.reject("No image selected")
            }
        } else {
            currentInvoke?.reject("User cancelled")
        }

        currentInvoke = null
    }

    private fun handlePickMultiplePhotosResult(result: ActivityResult) {
        if (result.resultCode == Activity.RESULT_OK) {
            val clipData = result.data?.clipData
            val photos = mutableListOf<JSObject>()

            if (clipData != null) {
                // Multiple images selected
                for (i in 0 until clipData.itemCount) {
                    val uri = clipData.getItemAt(i).uri
                    val bitmap = uriToBitmap(uri)
                    if (bitmap != null) {
                        val processedBitmap = processBitmap(bitmap)
                        photos.add(bitmapToResult(processedBitmap, null))
                    }
                }
            } else if (result.data?.data != null) {
                // Single image selected
                val uri = result.data?.data!!
                val bitmap = uriToBitmap(uri)
                if (bitmap != null) {
                    val processedBitmap = processBitmap(bitmap)
                    photos.add(bitmapToResult(processedBitmap, null))
                }
            }

            if (photos.isNotEmpty()) {
                val resultObj = JSObject()
                resultObj.put("photos", photos)
                currentInvoke?.resolve(resultObj)
            } else {
                currentInvoke?.reject("No images selected")
            }
        } else {
            currentInvoke?.reject("User cancelled")
        }

        currentInvoke = null
    }

    // MARK: - Permissions

    @Command
    fun checkPermissions(invoke: Invoke) {
        val cameraPermission = ContextCompat.checkSelfPermission(
            activity,
            Manifest.permission.CAMERA
        ) == PackageManager.PERMISSION_GRANTED

        val storagePermission = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            ContextCompat.checkSelfPermission(
                activity,
                Manifest.permission.READ_MEDIA_IMAGES
            ) == PackageManager.PERMISSION_GRANTED
        } else {
            ContextCompat.checkSelfPermission(
                activity,
                Manifest.permission.READ_EXTERNAL_STORAGE
            ) == PackageManager.PERMISSION_GRANTED
        }

        val result = JSObject()
        result.put("camera", if (cameraPermission) "granted" else "denied")
        result.put("photos", if (storagePermission) "granted" else "denied")

        invoke.resolve(result)
    }

    @Command
    fun requestPermissions(invoke: Invoke) {
        val permissions = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            arrayOf(Manifest.permission.CAMERA, Manifest.permission.READ_MEDIA_IMAGES)
        } else {
            arrayOf(Manifest.permission.CAMERA, Manifest.permission.READ_EXTERNAL_STORAGE)
        }

        activity.requestPermissions(permissions, 100)

        // Note: The result will be handled by onRequestPermissionsResult
        // For simplicity, we'll just return current status
        checkPermissions(invoke)
    }

    // MARK: - Helper Methods

    private fun createImageFile(): File {
        val filename = "photo_${UUID.randomUUID()}.jpg"
        val storageDir = activity.cacheDir
        return File(storageDir, filename)
    }

    private fun uriToBitmap(uri: Uri): Bitmap? {
        return try {
            val inputStream = activity.contentResolver.openInputStream(uri)
            BitmapFactory.decodeStream(inputStream)
        } catch (e: Exception) {
            null
        }
    }

    private fun processBitmap(bitmap: Bitmap): Bitmap {
        val width = maxWidth
        val height = maxHeight

        if (width != null && height != null) {
            return resizeBitmap(bitmap, width, height, preserveAspectRatio)
        }

        return bitmap
    }

    private fun resizeBitmap(
        bitmap: Bitmap,
        maxWidth: Int,
        maxHeight: Int,
        preserveAspectRatio: Boolean
    ): Bitmap {
        var newWidth = maxWidth
        var newHeight = maxHeight

        if (preserveAspectRatio) {
            val widthRatio = maxWidth.toFloat() / bitmap.width
            val heightRatio = maxHeight.toFloat() / bitmap.height
            val ratio = minOf(widthRatio, heightRatio)
            newWidth = (bitmap.width * ratio).toInt()
            newHeight = (bitmap.height * ratio).toInt()
        }

        return Bitmap.createScaledBitmap(bitmap, newWidth, newHeight, true)
    }

    private fun bitmapToResult(bitmap: Bitmap, file: File?): JSObject {
        val result = JSObject()
        result.put("format", resultType)
        result.put("width", bitmap.width)
        result.put("height", bitmap.height)

        when (resultType) {
            "base64" -> {
                val base64 = bitmapToBase64(bitmap)
                result.put("data", base64)
            }
            "dataUrl" -> {
                val base64 = bitmapToBase64(bitmap)
                result.put("data", "data:image/jpeg;base64,$base64")
            }
            else -> { // "uri"
                val path = file?.absolutePath ?: saveBitmapToFile(bitmap)
                result.put("data", path)
                result.put("path", path)
            }
        }

        return result
    }

    private fun bitmapToBase64(bitmap: Bitmap): String {
        val byteArrayOutputStream = ByteArrayOutputStream()
        bitmap.compress(Bitmap.CompressFormat.JPEG, quality, byteArrayOutputStream)
        val byteArray = byteArrayOutputStream.toByteArray()
        return Base64.encodeToString(byteArray, Base64.NO_WRAP)
    }

    private fun saveBitmapToFile(bitmap: Bitmap): String {
        val file = createImageFile()
        FileOutputStream(file).use { out ->
            bitmap.compress(Bitmap.CompressFormat.JPEG, quality, out)
        }
        return file.absolutePath
    }
}

// MARK: - Argument Classes

data class TakePhotoArgs(
    val quality: Int?,
    val allowEditing: Boolean?,
    val resultType: String?,
    val saveToGallery: Boolean?,
    val correctOrientation: Boolean?,
    val width: Int?,
    val height: Int?,
    val preserveAspectRatio: Boolean?
)

data class PickPhotoArgs(
    val quality: Int?,
    val allowEditing: Boolean?,
    val resultType: String?,
    val width: Int?,
    val height: Int?,
    val preserveAspectRatio: Boolean?
)

data class PickMultiplePhotosArgs(
    val limit: Int?,
    val quality: Int?,
    val resultType: String?
)

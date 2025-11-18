import UIKit
import Tauri
import Photos
import PhotosUI

/**
 * Camera Plugin for iOS
 *
 * Provides access to the device camera and photo library.
 * Supports taking photos, picking from gallery, and multiple photo selection.
 */
class CameraPlugin: Plugin {
    private var currentPicker: UIImagePickerController?
    private var currentPHPicker: PHPickerViewController?
    private var currentResolve: ((Result<[String: Any], Error>) -> Void)?

    override init() {
        super.init()
    }

    // MARK: - Take Photo

    @objc
    public func takePhoto(_ invoke: Invoke) throws {
        DispatchQueue.main.async { [weak self] in
            guard let self = self else { return }

            let args = invoke.parseArgs(TakePhotoArgs.self)

            // Check camera authorization
            let status = AVCaptureDevice.authorizationStatus(for: .video)

            if status == .denied || status == .restricted {
                invoke.reject("Camera permission denied")
                return
            }

            if status == .notDetermined {
                AVCaptureDevice.requestAccess(for: .video) { granted in
                    if granted {
                        DispatchQueue.main.async {
                            self.presentCamera(invoke: invoke, args: args)
                        }
                    } else {
                        invoke.reject("Camera permission denied")
                    }
                }
                return
            }

            self.presentCamera(invoke: invoke, args: args)
        }
    }

    private func presentCamera(invoke: Invoke, args: TakePhotoArgs?) {
        guard UIImagePickerController.isSourceTypeAvailable(.camera) else {
            invoke.reject("Camera not available on this device")
            return
        }

        let picker = UIImagePickerController()
        picker.sourceType = .camera
        picker.delegate = self
        picker.allowsEditing = args?.allowEditing ?? false

        if let mediaTypes = UIImagePickerController.availableMediaTypes(for: .camera) {
            picker.mediaTypes = mediaTypes.filter { $0 == "public.image" }
        }

        self.currentPicker = picker
        self.currentResolve = { result in
            invoke.resolve(result)
        }

        if let viewController = UIApplication.shared.keyWindow?.rootViewController {
            var presentingVC = viewController
            while let presented = presentingVC.presentedViewController {
                presentingVC = presented
            }
            presentingVC.present(picker, animated: true)
        }
    }

    // MARK: - Pick Photo

    @objc
    public func pickPhoto(_ invoke: Invoke) throws {
        DispatchQueue.main.async { [weak self] in
            guard let self = self else { return }

            let args = invoke.parseArgs(PickPhotoArgs.self)

            // Check photo library authorization
            let status = PHPhotoLibrary.authorizationStatus(for: .readWrite)

            if status == .denied || status == .restricted {
                invoke.reject("Photo library permission denied")
                return
            }

            if status == .notDetermined {
                PHPhotoLibrary.requestAuthorization(for: .readWrite) { newStatus in
                    if newStatus == .authorized || newStatus == .limited {
                        DispatchQueue.main.async {
                            self.presentPhotoPicker(invoke: invoke, args: args, multiple: false)
                        }
                    } else {
                        invoke.reject("Photo library permission denied")
                    }
                }
                return
            }

            self.presentPhotoPicker(invoke: invoke, args: args, multiple: false)
        }
    }

    // MARK: - Pick Multiple Photos

    @objc
    public func pickMultiplePhotos(_ invoke: Invoke) throws {
        DispatchQueue.main.async { [weak self] in
            guard let self = self else { return }

            let args = invoke.parseArgs(PickMultiplePhotosArgs.self)

            // Check photo library authorization
            let status = PHPhotoLibrary.authorizationStatus(for: .readWrite)

            if status == .denied || status == .restricted {
                invoke.reject("Photo library permission denied")
                return
            }

            if status == .notDetermined {
                PHPhotoLibrary.requestAuthorization(for: .readWrite) { newStatus in
                    if newStatus == .authorized || newStatus == .limited {
                        DispatchQueue.main.async {
                            self.presentPhotoPicker(invoke: invoke, args: args, multiple: true)
                        }
                    } else {
                        invoke.reject("Photo library permission denied")
                    }
                }
                return
            }

            self.presentPhotoPicker(invoke: invoke, args: args, multiple: true)
        }
    }

    private func presentPhotoPicker(invoke: Invoke, args: Any?, multiple: Bool) {
        if #available(iOS 14.0, *) {
            var configuration = PHPickerConfiguration()
            configuration.filter = .images

            if multiple {
                if let multiArgs = args as? PickMultiplePhotosArgs {
                    configuration.selectionLimit = multiArgs.limit ?? 10
                } else {
                    configuration.selectionLimit = 10
                }
            } else {
                configuration.selectionLimit = 1
            }

            let picker = PHPickerViewController(configuration: configuration)
            picker.delegate = self

            self.currentPHPicker = picker
            self.currentResolve = { result in
                invoke.resolve(result)
            }

            if let viewController = UIApplication.shared.keyWindow?.rootViewController {
                var presentingVC = viewController
                while let presented = presentingVC.presentedViewController {
                    presentingVC = presented
                }
                presentingVC.present(picker, animated: true)
            }
        } else {
            // Fallback for iOS < 14
            self.presentLegacyPhotoPicker(invoke: invoke, args: args)
        }
    }

    private func presentLegacyPhotoPicker(invoke: Invoke, args: Any?) {
        let picker = UIImagePickerController()
        picker.sourceType = .photoLibrary
        picker.delegate = self

        if let pickArgs = args as? PickPhotoArgs {
            picker.allowsEditing = pickArgs.allowEditing ?? false
        }

        self.currentPicker = picker
        self.currentResolve = { result in
            invoke.resolve(result)
        }

        if let viewController = UIApplication.shared.keyWindow?.rootViewController {
            var presentingVC = viewController
            while let presented = presentingVC.presentedViewController {
                presentingVC = presented
            }
            presentingVC.present(picker, animated: true)
        }
    }

    // MARK: - Permissions

    @objc
    public func checkPermissions(_ invoke: Invoke) throws {
        let cameraStatus = AVCaptureDevice.authorizationStatus(for: .video)
        let photoStatus = PHPhotoLibrary.authorizationStatus(for: .readWrite)

        invoke.resolve([
            "camera": authStatusToString(cameraStatus),
            "photos": photoAuthStatusToString(photoStatus)
        ])
    }

    @objc
    public func requestPermissions(_ invoke: Invoke) throws {
        let group = DispatchGroup()
        var results: [String: String] = [:]

        // Request camera permission
        group.enter()
        AVCaptureDevice.requestAccess(for: .video) { granted in
            let status = AVCaptureDevice.authorizationStatus(for: .video)
            results["camera"] = self.authStatusToString(status)
            group.leave()
        }

        // Request photo library permission
        group.enter()
        PHPhotoLibrary.requestAuthorization(for: .readWrite) { status in
            results["photos"] = self.photoAuthStatusToString(status)
            group.leave()
        }

        group.notify(queue: .main) {
            invoke.resolve(results)
        }
    }

    // MARK: - Helper Methods

    private func authStatusToString(_ status: AVAuthorizationStatus) -> String {
        switch status {
        case .authorized:
            return "granted"
        case .denied, .restricted:
            return "denied"
        case .notDetermined:
            return "prompt"
        @unknown default:
            return "denied"
        }
    }

    private func photoAuthStatusToString(_ status: PHAuthorizationStatus) -> String {
        switch status {
        case .authorized, .limited:
            return "granted"
        case .denied, .restricted:
            return "denied"
        case .notDetermined:
            return "prompt"
        @unknown default:
            return "denied"
        }
    }

    private func processImage(_ image: UIImage, args: TakePhotoArgs?) -> [String: Any] {
        var resultImage = image

        // Apply size constraints if specified
        if let width = args?.width, let height = args?.height {
            let targetSize = CGSize(width: width, height: height)
            resultImage = resizeImage(image, targetSize: targetSize, preserveAspectRatio: args?.preserveAspectRatio ?? true)
        }

        // Get result type
        let resultType = args?.resultType ?? "uri"
        let quality = CGFloat((args?.quality ?? 90)) / 100.0

        var result: [String: Any] = [
            "format": resultType,
            "width": Int(resultImage.size.width),
            "height": Int(resultImage.size.height)
        ]

        switch resultType {
        case "base64":
            if let imageData = resultImage.jpegData(compressionQuality: quality) {
                result["data"] = imageData.base64EncodedString()
            }
        case "dataUrl":
            if let imageData = resultImage.jpegData(compressionQuality: quality) {
                let base64 = imageData.base64EncodedString()
                result["data"] = "data:image/jpeg;base64,\(base64)"
            }
        default: // "uri"
            if let imageData = resultImage.jpegData(compressionQuality: quality) {
                let path = saveImageToTempDirectory(imageData)
                result["data"] = path
                result["path"] = path
            }
        }

        return result
    }

    private func resizeImage(_ image: UIImage, targetSize: CGSize, preserveAspectRatio: Bool) -> UIImage {
        var newSize = targetSize

        if preserveAspectRatio {
            let widthRatio = targetSize.width / image.size.width
            let heightRatio = targetSize.height / image.size.height
            let ratio = min(widthRatio, heightRatio)
            newSize = CGSize(width: image.size.width * ratio, height: image.size.height * ratio)
        }

        UIGraphicsBeginImageContextWithOptions(newSize, false, 1.0)
        image.draw(in: CGRect(origin: .zero, size: newSize))
        let resizedImage = UIGraphicsGetImageFromCurrentImageContext()
        UIGraphicsEndImageContext()

        return resizedImage ?? image
    }

    private func saveImageToTempDirectory(_ imageData: Data) -> String {
        let filename = "photo_\(UUID().uuidString).jpg"
        let tempDir = NSTemporaryDirectory()
        let filePath = (tempDir as NSString).appendingPathComponent(filename)

        do {
            try imageData.write(to: URL(fileURLWithPath: filePath))
            return filePath
        } catch {
            return ""
        }
    }
}

// MARK: - UIImagePickerControllerDelegate

extension CameraPlugin: UIImagePickerControllerDelegate, UINavigationControllerDelegate {
    func imagePickerController(_ picker: UIImagePickerController, didFinishPickingMediaWithInfo info: [UIImagePickerController.InfoKey: Any]) {
        picker.dismiss(animated: true)

        var image: UIImage?
        if let editedImage = info[.editedImage] as? UIImage {
            image = editedImage
        } else if let originalImage = info[.originalImage] as? UIImage {
            image = originalImage
        }

        guard let finalImage = image else {
            self.currentResolve?(.failure(NSError(domain: "CameraPlugin", code: -1, userInfo: [NSLocalizedDescriptionKey: "Failed to get image"])))
            return
        }

        let result = processImage(finalImage, args: nil)
        self.currentResolve?(.success(result))

        self.currentPicker = nil
        self.currentResolve = nil
    }

    func imagePickerControllerDidCancel(_ picker: UIImagePickerController) {
        picker.dismiss(animated: true)
        self.currentResolve?(.failure(NSError(domain: "CameraPlugin", code: -2, userInfo: [NSLocalizedDescriptionKey: "User cancelled"])))
        self.currentPicker = nil
        self.currentResolve = nil
    }
}

// MARK: - PHPickerViewControllerDelegate

@available(iOS 14.0, *)
extension CameraPlugin: PHPickerViewControllerDelegate {
    func picker(_ picker: PHPickerViewController, didFinishPicking results: [PHPickerResult]) {
        picker.dismiss(animated: true)

        guard !results.isEmpty else {
            self.currentResolve?(.failure(NSError(domain: "CameraPlugin", code: -2, userInfo: [NSLocalizedDescriptionKey: "User cancelled"])))
            self.currentPHPicker = nil
            self.currentResolve = nil
            return
        }

        if results.count == 1 {
            // Single photo
            let result = results[0]
            if result.itemProvider.canLoadObject(ofClass: UIImage.self) {
                result.itemProvider.loadObject(ofClass: UIImage.self) { [weak self] image, error in
                    guard let self = self, let image = image as? UIImage else {
                        self?.currentResolve?(.failure(error ?? NSError(domain: "CameraPlugin", code: -1, userInfo: [NSLocalizedDescriptionKey: "Failed to load image"])))
                        return
                    }

                    let photoResult = self.processImage(image, args: nil)
                    self.currentResolve?(.success(photoResult))
                    self.currentPHPicker = nil
                    self.currentResolve = nil
                }
            }
        } else {
            // Multiple photos
            var photos: [[String: Any]] = []
            let group = DispatchGroup()

            for result in results {
                if result.itemProvider.canLoadObject(ofClass: UIImage.self) {
                    group.enter()
                    result.itemProvider.loadObject(ofClass: UIImage.self) { [weak self] image, error in
                        if let image = image as? UIImage {
                            let photoResult = self?.processImage(image, args: nil) ?? [:]
                            photos.append(photoResult)
                        }
                        group.leave()
                    }
                }
            }

            group.notify(queue: .main) { [weak self] in
                self?.currentResolve?(.success(["photos": photos]))
                self?.currentPHPicker = nil
                self?.currentResolve = nil
            }
        }
    }
}

// MARK: - Argument Structs

struct TakePhotoArgs: Decodable {
    let quality: Int?
    let allowEditing: Bool?
    let resultType: String?
    let saveToGallery: Bool?
    let correctOrientation: Bool?
    let width: Int?
    let height: Int?
    let preserveAspectRatio: Bool?
}

struct PickPhotoArgs: Decodable {
    let quality: Int?
    let allowEditing: Bool?
    let resultType: String?
    let width: Int?
    let height: Int?
    let preserveAspectRatio: Bool?
}

struct PickMultiplePhotosArgs: Decodable {
    let limit: Int?
    let quality: Int?
    let resultType: String?
}

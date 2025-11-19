<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mucan54\TauriPhp\Facades\Camera;
use Mucan54\TauriPhp\Facades\Geolocation;
use Mucan54\TauriPhp\Facades\Notification;
use Mucan54\TauriPhp\Facades\Storage;
use Mucan54\TauriPhp\Facades\Vibration;

/**
 * Example controller demonstrating Tauri mobile plugin usage.
 *
 * This controller shows how to use all available mobile plugins
 * in a Laravel application with the tauri-php package.
 */
class MobilePluginExampleController extends Controller
{
    /**
     * Camera Example: Take a photo
     *
     * Route: POST /api/mobile/camera/take
     */
    public function takePhoto(Request $request): JsonResponse
    {
        try {
            // Take a photo with custom options
            $photo = Camera::takePhoto([
                'quality' => 85,
                'allowEditing' => true,
                'resultType' => 'uri', // 'uri', 'base64', or 'dataUrl'
                'saveToGallery' => false,
                'width' => 1920,
                'height' => 1080,
            ]);

            // Save to storage
            $path = storage_path('app/photos/'.uniqid().'.jpg');
            $photo->saveTo($path);

            return response()->json([
                'success' => true,
                'photo' => [
                    'path' => $path,
                    'width' => $photo->width,
                    'height' => $photo->height,
                    'dimensions' => $photo->getDimensions(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Camera Example: Pick photo from gallery
     *
     * Route: POST /api/mobile/camera/pick
     */
    public function pickPhoto(): JsonResponse
    {
        try {
            $photo = Camera::pickPhoto([
                'quality' => 90,
                'resultType' => 'base64',
            ]);

            // Get as base64 for immediate display
            $base64Data = $photo->toBase64();

            return response()->json([
                'success' => true,
                'photo' => [
                    'data' => $base64Data,
                    'width' => $photo->width,
                    'height' => $photo->height,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Camera Example: Pick multiple photos
     *
     * Route: POST /api/mobile/camera/pick-multiple
     */
    public function pickMultiplePhotos(): JsonResponse
    {
        try {
            $photos = Camera::pickMultiplePhotos([
                'limit' => 5,
            ]);

            $photoData = array_map(function ($photo) {
                return [
                    'path' => $photo->getPath(),
                    'width' => $photo->width,
                    'height' => $photo->height,
                ];
            }, $photos);

            return response()->json([
                'success' => true,
                'count' => count($photos),
                'photos' => $photoData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Notification Example: Schedule a notification
     *
     * Route: POST /api/mobile/notification/schedule
     */
    public function scheduleNotification(Request $request): JsonResponse
    {
        try {
            $result = Notification::schedule([
                'title' => 'Reminder',
                'body' => 'Don\'t forget to check your tasks!',
                'schedule' => [
                    'at' => now()->addMinutes(5)->toIso8601String(),
                ],
                'sound' => 'default',
                'badge' => 1,
            ]);

            return response()->json([
                'success' => true,
                'notification' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Notification Example: Show instant notification
     *
     * Route: POST /api/mobile/notification/instant
     */
    public function showInstantNotification(Request $request): JsonResponse
    {
        try {
            $result = Notification::schedule([
                'title' => $request->input('title', 'Hello!'),
                'body' => $request->input('body', 'This is an instant notification'),
                'schedule' => [
                    'at' => now()->toIso8601String(), // Show immediately
                ],
            ]);

            return response()->json([
                'success' => true,
                'notification' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Notification Example: Get pending notifications
     *
     * Route: GET /api/mobile/notification/pending
     */
    public function getPendingNotifications(): JsonResponse
    {
        try {
            $notifications = Notification::getPending();

            return response()->json([
                'success' => true,
                'count' => count($notifications),
                'notifications' => $notifications,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vibration Example: Simple vibration
     *
     * Route: POST /api/mobile/vibration/simple
     */
    public function vibrateSimple(): JsonResponse
    {
        try {
            Vibration::vibrate(300); // 300ms

            return response()->json([
                'success' => true,
                'message' => 'Vibrated for 300ms',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vibration Example: Pattern vibration
     *
     * Route: POST /api/mobile/vibration/pattern
     */
    public function vibratePattern(): JsonResponse
    {
        try {
            // Vibrate in pattern: [vibrate, pause, vibrate, pause, vibrate]
            Vibration::vibratePattern([100, 200, 100, 200, 100]);

            return response()->json([
                'success' => true,
                'message' => 'Vibration pattern executed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vibration Example: Haptic feedback
     *
     * Route: POST /api/mobile/vibration/haptic
     */
    public function hapticFeedback(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'success'); // success, warning, error

            Vibration::notification($type);

            return response()->json([
                'success' => true,
                'message' => "Haptic feedback: {$type}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Geolocation Example: Get current position
     *
     * Route: GET /api/mobile/geolocation/current
     */
    public function getCurrentLocation(): JsonResponse
    {
        try {
            $position = Geolocation::getCurrentPosition([
                'enableHighAccuracy' => true,
                'timeout' => 10000,
                'maximumAge' => 0,
            ]);

            return response()->json([
                'success' => true,
                'position' => [
                    'latitude' => $position->latitude,
                    'longitude' => $position->longitude,
                    'accuracy' => $position->accuracy,
                    'altitude' => $position->altitude,
                    'speed' => $position->speed,
                    'heading' => $position->heading,
                    'timestamp' => $position->timestamp,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Storage Example: Store data
     *
     * Route: POST /api/mobile/storage/set
     */
    public function storeData(Request $request): JsonResponse
    {
        try {
            $key = $request->input('key', 'example_key');
            $value = $request->input('value', 'example_value');

            Storage::set($key, $value);

            return response()->json([
                'success' => true,
                'message' => "Stored '{$key}' successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Storage Example: Get data
     *
     * Route: GET /api/mobile/storage/get
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $key = $request->input('key', 'example_key');
            $value = Storage::get($key);

            return response()->json([
                'success' => true,
                'key' => $key,
                'value' => $value,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Storage Example: Store multiple items
     *
     * Route: POST /api/mobile/storage/set-multiple
     */
    public function storeMultipleData(Request $request): JsonResponse
    {
        try {
            $items = $request->input('items', [
                'user_name' => 'John Doe',
                'user_email' => 'john@example.com',
                'user_age' => 25,
            ]);

            Storage::setMultiple($items);

            return response()->json([
                'success' => true,
                'message' => 'Stored '.count($items).' items successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Storage Example: Get all keys
     *
     * Route: GET /api/mobile/storage/keys
     */
    public function getStorageKeys(): JsonResponse
    {
        try {
            $keys = Storage::keys();

            return response()->json([
                'success' => true,
                'count' => count($keys),
                'keys' => $keys,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Permissions Example: Check all plugin permissions
     *
     * Route: GET /api/mobile/permissions/check
     */
    public function checkPermissions(): JsonResponse
    {
        try {
            $permissions = [
                'camera' => Camera::checkPermissions(),
                'notification' => Notification::checkPermissions(),
                'geolocation' => Geolocation::checkPermissions(),
            ];

            return response()->json([
                'success' => true,
                'permissions' => $permissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Permissions Example: Request all plugin permissions
     *
     * Route: POST /api/mobile/permissions/request
     */
    public function requestPermissions(): JsonResponse
    {
        try {
            $permissions = [
                'camera' => Camera::requestPermissions(),
                'notification' => Notification::requestPermissions(),
                'geolocation' => Geolocation::requestPermissions(),
            ];

            return response()->json([
                'success' => true,
                'permissions' => $permissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

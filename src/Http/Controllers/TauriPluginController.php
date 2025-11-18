<?php

namespace Mucan54\TauriPhp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

/**
 * Controller for handling Tauri plugin bridge communication.
 *
 * This controller manages the bidirectional communication between
 * the Tauri JavaScript bridge and Laravel backend for plugin calls.
 */
class TauriPluginController extends Controller
{
    /**
     * Mark the Tauri mobile environment as active.
     *
     * Called by the JavaScript bridge when it initializes.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function markActive(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();

        Cache::put("tauri_active_{$sessionId}", true, now()->addHours(2));

        return response()->json([
            'success' => true,
            'message' => 'Tauri environment marked as active',
        ]);
    }

    /**
     * Get pending plugin calls for execution.
     *
     * The JavaScript bridge polls this endpoint to fetch pending
     * plugin commands that need to be executed via Tauri.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getPluginCalls(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $cacheKey = "tauri_plugin_calls_{$sessionId}";

        // Get all pending calls for this session
        $calls = Cache::get($cacheKey, []);

        // Filter out calls that have been picked up
        $pendingCalls = array_filter($calls, function ($call) {
            return ! isset($call['picked_up']) || ! $call['picked_up'];
        });

        // Mark all returned calls as picked up
        foreach ($pendingCalls as $id => $call) {
            $calls[$id]['picked_up'] = true;
        }

        Cache::put($cacheKey, $calls, now()->addMinutes(30));

        return response()->json(array_values($pendingCalls));
    }

    /**
     * Receive plugin execution results from the JavaScript bridge.
     *
     * After the Tauri bridge executes a plugin command, it sends
     * the result back through this endpoint.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function receivePluginResponse(Request $request): JsonResponse
    {
        $callId = $request->input('call_id');
        $result = $request->input('result');
        $error = $request->input('error');

        $sessionId = $request->session()->getId();
        $resultKey = "tauri_plugin_result_{$sessionId}_{$callId}";

        // Store the result for the waiting PHP code to retrieve
        Cache::put($resultKey, [
            'result' => $result,
            'error' => $error,
            'completed' => true,
        ], now()->addMinutes(5));

        return response()->json([
            'success' => true,
            'message' => 'Plugin response received',
        ]);
    }

    /**
     * Queue a plugin call for execution.
     *
     * Internal method used by Plugin base class to queue calls
     * for the JavaScript bridge to pick up.
     *
     * @param  string  $plugin
     * @param  string  $command
     * @param  array  $args
     * @param  string  $sessionId
     * @return string Call ID
     */
    public static function queuePluginCall(
        string $plugin,
        string $command,
        array $args,
        string $sessionId
    ): string {
        $callId = uniqid('call_', true);
        $cacheKey = "tauri_plugin_calls_{$sessionId}";

        $calls = Cache::get($cacheKey, []);

        $calls[$callId] = [
            'id' => $callId,
            'plugin' => $plugin,
            'command' => $command,
            'args' => $args,
            'picked_up' => false,
            'queued_at' => time(),
        ];

        Cache::put($cacheKey, $calls, now()->addMinutes(30));

        return $callId;
    }

    /**
     * Wait for a plugin call result.
     *
     * Internal method used by Plugin base class to wait for
     * the JavaScript bridge to complete a plugin call.
     *
     * @param  string  $callId
     * @param  string  $sessionId
     * @param  int  $timeout  Timeout in seconds
     * @return array Result or error
     *
     * @throws \Exception
     */
    public static function waitForResult(
        string $callId,
        string $sessionId,
        int $timeout = 30
    ): array {
        $resultKey = "tauri_plugin_result_{$sessionId}_{$callId}";
        $startTime = time();

        while (true) {
            $result = Cache::get($resultKey);

            if ($result && isset($result['completed'])) {
                // Clean up
                Cache::forget($resultKey);

                if ($result['error']) {
                    throw new \Exception("Plugin error: {$result['error']}");
                }

                return $result['result'];
            }

            // Check timeout
            if (time() - $startTime > $timeout) {
                throw new \Exception('Plugin call timed out after '.$timeout.' seconds');
            }

            // Wait before checking again
            usleep(100000); // 100ms
        }
    }

    /**
     * Check if Tauri mobile environment is active.
     *
     * @param  string  $sessionId
     * @return bool
     */
    public static function isTauriActive(string $sessionId): bool
    {
        return Cache::get("tauri_active_{$sessionId}", false);
    }
}

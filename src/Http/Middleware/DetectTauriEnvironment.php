<?php

namespace Mucan54\TauriPhp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to detect and mark Tauri mobile environment.
 *
 * This middleware checks if the request is coming from a Tauri mobile
 * application and provides helper methods to determine the environment.
 */
class DetectTauriEnvironment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for Tauri-specific headers or user agent
        $isTauri = $this->detectTauriEnvironment($request);

        // Store in request for easy access
        $request->attributes->set('is_tauri', $isTauri);

        // Also make it available globally
        if ($isTauri) {
            config(['tauri.is_active' => true]);
        }

        return $next($request);
    }

    /**
     * Detect if the request is from a Tauri mobile application.
     *
     * @param  Request  $request
     * @return bool
     */
    protected function detectTauriEnvironment(Request $request): bool
    {
        // Check for custom Tauri header
        if ($request->hasHeader('X-Tauri-App')) {
            return true;
        }

        // Check for Tauri user agent patterns
        $userAgent = $request->userAgent();

        if ($userAgent && (
            str_contains($userAgent, 'Tauri') ||
            str_contains($userAgent, 'TauriApp')
        )) {
            return true;
        }

        // Check if session has been marked active by the JavaScript bridge
        if (session()->has('tauri_mobile_active')) {
            return true;
        }

        return false;
    }
}

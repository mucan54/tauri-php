/**
 * Tauri-PHP Bridge
 *
 * This script bridges Tauri mobile plugins with Laravel backend.
 * It listens for plugin calls from Laravel and executes them via Tauri,
 * then sends the results back to Laravel.
 */

import { invoke } from '@tauri-apps/api/core';

class TauriPhpBridge {
    constructor() {
        this.pendingCalls = new Map();
        this.callId = 0;
        this.apiEndpoint = '/api/tauri/plugin-response';
        this.init();
    }

    init() {
        // Mark Tauri as active
        this.markTauriActive();

        // Listen for plugin commands from Laravel
        this.startListening();

        console.log('[Tauri-PHP Bridge] Initialized');
    }

    /**
     * Mark Tauri mobile environment as active
     */
    async markTauriActive() {
        try {
            await fetch('/api/tauri/mark-active', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify({ active: true })
            });
        } catch (error) {
            console.error('[Tauri-PHP Bridge] Failed to mark active:', error);
        }
    }

    /**
     * Start listening for plugin commands
     */
    startListening() {
        // Poll for pending plugin calls
        setInterval(() => this.checkPendingCalls(), 500);
    }

    /**
     * Check for pending plugin calls from Laravel
     */
    async checkPendingCalls() {
        try {
            const response = await fetch('/api/tauri/plugin-calls', {
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                }
            });

            if (!response.ok) return;

            const calls = await response.json();

            for (const call of calls) {
                this.executePluginCall(call);
            }
        } catch (error) {
            // Silently fail - Laravel might not be ready
        }
    }

    /**
     * Execute a plugin call via Tauri
     */
    async executePluginCall(call) {
        const { id, plugin, command, args } = call;

        try {
            console.log(`[Tauri-PHP Bridge] Executing ${plugin}.${command}`, args);

            // Call Tauri plugin
            const result = await invoke(`plugin:${plugin}|${command}`, args || {});

            // Send result back to Laravel
            await this.sendResult(id, result, null);

        } catch (error) {
            console.error(`[Tauri-PHP Bridge] Error executing ${plugin}.${command}:`, error);
            await this.sendResult(id, null, error.toString());
        }
    }

    /**
     * Send result back to Laravel
     */
    async sendResult(callId, result, error) {
        try {
            await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify({
                    call_id: callId,
                    result: result,
                    error: error,
                })
            });
        } catch (err) {
            console.error('[Tauri-PHP Bridge] Failed to send result:', err);
        }
    }

    /**
     * Get CSRF token from meta tag or cookie
     */
    getCsrfToken() {
        // Try meta tag first
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.getAttribute('content');
        }

        // Try cookie
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'XSRF-TOKEN') {
                return decodeURIComponent(value);
            }
        }

        return '';
    }

    /**
     * Call a plugin directly from JavaScript
     */
    async call(plugin, command, args = {}) {
        try {
            const result = await invoke(`plugin:${plugin}|${command}`, args);
            return { success: true, data: result };
        } catch (error) {
            return { success: false, error: error.toString() };
        }
    }
}

// Initialize bridge when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.tauriPhpBridge = new TauriPhpBridge();
    });
} else {
    window.tauriPhpBridge = new TauriPhpBridge();
}

// Export for module usage
export default TauriPhpBridge;

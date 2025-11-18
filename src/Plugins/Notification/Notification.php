<?php

namespace Mucan54\TauriPhp\Plugins\Notification;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Plugins\Plugin;

/**
 * Notification plugin for Tauri mobile applications.
 *
 * Provides access to local push notifications:
 * - Schedule local notifications
 * - Show instant notifications
 * - Manage notification channels (Android)
 * - Handle notification actions
 */
class Notification extends Plugin
{
    /**
     * The plugin name.
     *
     * @var string
     */
    protected $pluginName = 'notification';

    /**
     * Schedule a local notification.
     *
     * @param  array  $options  Notification options
     * @return array Scheduled notification info
     *
     * @throws TauriPhpException
     *
     * Options:
     * - title: string - Notification title (required)
     * - body: string - Notification body
     * - id: int - Notification ID (auto-generated if not provided)
     * - schedule: array - When to show (at, every, on, etc.)
     * - sound: string - Sound file name
     * - badge: int - Badge number (iOS)
     * - icon: string - Icon name (Android)
     * - actionTypeId: string - Action type identifier
     * - extra: array - Additional data
     * - ongoing: bool - Persistent notification (Android)
     * - autoCancel: bool - Auto dismiss on tap (Android, default: true)
     * - channelId: string - Channel ID (Android)
     */
    public function schedule(array $options): array
    {
        $this->validateNotificationOptions($options);

        return $this->invoke('schedule', ['notifications' => [$options]]);
    }

    /**
     * Schedule multiple notifications at once.
     *
     * @param  array  $notifications  Array of notification options
     * @return array Scheduled notifications info
     *
     * @throws TauriPhpException
     */
    public function scheduleMultiple(array $notifications): array
    {
        foreach ($notifications as $notification) {
            $this->validateNotificationOptions($notification);
        }

        return $this->invoke('schedule', ['notifications' => $notifications]);
    }

    /**
     * Get list of pending notifications.
     *
     * @return array Pending notifications
     *
     * @throws TauriPhpException
     */
    public function getPending(): array
    {
        $result = $this->invoke('getPending', []);

        return $result['notifications'] ?? [];
    }

    /**
     * Cancel a scheduled notification.
     *
     * @param  int  $id  Notification ID
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function cancel(int $id): array
    {
        return $this->invoke('cancel', ['notifications' => [['id' => $id]]]);
    }

    /**
     * Cancel multiple notifications.
     *
     * @param  array  $ids  Array of notification IDs
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function cancelMultiple(array $ids): array
    {
        $notifications = array_map(fn ($id) => ['id' => $id], $ids);

        return $this->invoke('cancel', ['notifications' => $notifications]);
    }

    /**
     * Cancel all pending notifications.
     *
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function cancelAll(): array
    {
        return $this->invoke('cancelAll', []);
    }

    /**
     * Get delivered notifications.
     *
     * @return array Delivered notifications
     *
     * @throws TauriPhpException
     */
    public function getDelivered(): array
    {
        $result = $this->invoke('getDelivered', []);

        return $result['notifications'] ?? [];
    }

    /**
     * Remove delivered notifications.
     *
     * @param  array  $ids  Array of notification IDs
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function removeDelivered(array $ids): array
    {
        $notifications = array_map(fn ($id) => ['id' => $id], $ids);

        return $this->invoke('removeDelivered', ['notifications' => $notifications]);
    }

    /**
     * Remove all delivered notifications.
     *
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function removeAllDelivered(): array
    {
        return $this->invoke('removeAllDelivered', []);
    }

    /**
     * Create a notification channel (Android only).
     *
     * @param  array  $channel  Channel configuration
     * @return array Operation result
     *
     * @throws TauriPhpException
     *
     * Channel options:
     * - id: string - Channel ID (required)
     * - name: string - Channel name (required)
     * - description: string - Channel description
     * - importance: int - 1-5 (default: 3)
     * - visibility: int - Lockscreen visibility
     * - sound: string - Sound file name
     * - vibration: bool - Enable vibration
     * - lights: bool - Enable LED lights
     */
    public function createChannel(array $channel): array
    {
        return $this->invoke('createChannel', $channel);
    }

    /**
     * Delete a notification channel (Android only).
     *
     * @param  string  $channelId  Channel ID
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function deleteChannel(string $channelId): array
    {
        return $this->invoke('deleteChannel', ['id' => $channelId]);
    }

    /**
     * List all notification channels (Android only).
     *
     * @return array Channels list
     *
     * @throws TauriPhpException
     */
    public function listChannels(): array
    {
        $result = $this->invoke('listChannels', []);

        return $result['channels'] ?? [];
    }

    /**
     * Validate notification options.
     *
     * @throws TauriPhpException
     */
    protected function validateNotificationOptions(array $options): void
    {
        if (empty($options['title'])) {
            throw TauriPhpException::pluginError(
                $this->pluginName,
                'Notification title is required'
            );
        }
    }
}

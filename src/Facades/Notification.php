<?php

namespace Mucan54\TauriPhp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Notification Facade
 *
 * @method static array schedule(array $options)
 * @method static array scheduleMultiple(array $notifications)
 * @method static array getPending()
 * @method static array cancel(int $id)
 * @method static array cancelMultiple(array $ids)
 * @method static array cancelAll()
 * @method static array getDelivered()
 * @method static array removeDelivered(array $ids)
 * @method static array removeAllDelivered()
 * @method static array createChannel(array $channel)
 * @method static array deleteChannel(string $channelId)
 * @method static array listChannels()
 * @method static array requestPermissions()
 * @method static array checkPermissions()
 *
 * @see \Mucan54\TauriPhp\Plugins\Notification\Notification
 */
class Notification extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Mucan54\TauriPhp\Plugins\Notification\Notification::class;
    }
}

<?php

namespace Mucan54\TauriPhp\Plugins\Geolocation;

/**
 * Represents a geographic position.
 */
class Position
{
    /**
     * Latitude in decimal degrees.
     *
     * @var float
     */
    public $latitude;

    /**
     * Longitude in decimal degrees.
     *
     * @var float
     */
    public $longitude;

    /**
     * Accuracy in meters.
     *
     * @var float
     */
    public $accuracy;

    /**
     * Altitude in meters (null if not available).
     *
     * @var float|null
     */
    public $altitude;

    /**
     * Altitude accuracy in meters.
     *
     * @var float|null
     */
    public $altitudeAccuracy;

    /**
     * Heading in degrees (0-360).
     *
     * @var float|null
     */
    public $heading;

    /**
     * Speed in meters per second.
     *
     * @var float|null
     */
    public $speed;

    /**
     * Timestamp when position was acquired.
     *
     * @var int
     */
    public $timestamp;

    /**
     * Create a new Position instance.
     */
    public function __construct(array $data)
    {
        $coords = $data['coords'] ?? $data;

        $this->latitude = $coords['latitude'] ?? 0.0;
        $this->longitude = $coords['longitude'] ?? 0.0;
        $this->accuracy = $coords['accuracy'] ?? 0.0;
        $this->altitude = $coords['altitude'] ?? null;
        $this->altitudeAccuracy = $coords['altitudeAccuracy'] ?? null;
        $this->heading = $coords['heading'] ?? null;
        $this->speed = $coords['speed'] ?? null;
        $this->timestamp = $data['timestamp'] ?? time() * 1000;
    }

    /**
     * Get coordinates as array.
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'altitude' => $this->altitude,
            'altitudeAccuracy' => $this->altitudeAccuracy,
            'heading' => $this->heading,
            'speed' => $this->speed,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Calculate distance to another position (in meters).
     * Uses Haversine formula.
     */
    public function distanceTo(Position $position): float
    {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($position->latitude);
        $deltaLat = deg2rad($position->latitude - $this->latitude);
        $deltaLon = deg2rad($position->longitude - $this->longitude);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get coordinates as Google Maps URL.
     */
    public function toGoogleMapsUrl(): string
    {
        return sprintf(
            'https://www.google.com/maps?q=%f,%f',
            $this->latitude,
            $this->longitude
        );
    }

    /**
     * Get coordinates as string (latitude, longitude).
     */
    public function toString(): string
    {
        return sprintf('%f, %f', $this->latitude, $this->longitude);
    }

    /**
     * Magic method for string conversion.
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}

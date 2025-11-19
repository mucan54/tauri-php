<?php

namespace Mucan54\TauriPhp\Plugins\Camera;

/**
 * Represents the result of a camera operation.
 */
class CameraResult
{
    /**
     * The photo data or path.
     *
     * @var string
     */
    public $data;

    /**
     * The result format ('base64', 'uri', 'dataUrl').
     *
     * @var string
     */
    public $format;

    /**
     * The file path (if saved).
     *
     * @var string|null
     */
    public $path;

    /**
     * Image width in pixels.
     *
     * @var int|null
     */
    public $width;

    /**
     * Image height in pixels.
     *
     * @var int|null
     */
    public $height;

    /**
     * Image EXIF data.
     *
     * @var array|null
     */
    public $exif;

    /**
     * Create a new CameraResult instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data['data'] ?? $data['path'] ?? '';
        $this->format = $data['format'] ?? 'uri';
        $this->path = $data['path'] ?? null;
        $this->width = $data['width'] ?? null;
        $this->height = $data['height'] ?? null;
        $this->exif = $data['exif'] ?? null;
    }

    /**
     * Get the base64 encoded image data.
     */
    public function toBase64(): string
    {
        if ($this->format === 'base64') {
            return $this->data;
        }

        if ($this->path && file_exists($this->path)) {
            return base64_encode(file_get_contents($this->path));
        }

        return '';
    }

    /**
     * Get the file path.
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Get image dimensions.
     */
    public function getDimensions(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * Get EXIF data.
     */
    public function getExif(): ?array
    {
        return $this->exif;
    }

    /**
     * Save the photo to a specific path.
     */
    public function saveTo(string $destinationPath): bool
    {
        if ($this->format === 'base64') {
            return file_put_contents($destinationPath, base64_decode($this->data)) !== false;
        }

        if ($this->path && file_exists($this->path)) {
            return copy($this->path, $destinationPath);
        }

        return false;
    }
}

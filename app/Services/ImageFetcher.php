<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ImageFetcher
{
    private string $pexelsApiKey;
    private string $storagePath;

    public function __construct()
    {
        $this->pexelsApiKey = config('services.pexels.key') ?? '';
        $this->storagePath = public_path('images/entities');
    }

    /**
     * Fetch image for entity from Pexels API, download locally, and create icon
     */
    public function fetchForEntity(string $entityType, int $entityId, string $name): ?string
    {
        $pexelsUrl = $this->searchPexels($name);

        if (!$pexelsUrl) {
            return null;
        }

        return $this->downloadAndCreateIcon($entityType, $entityId, $pexelsUrl);
    }

    /**
     * Search Pexels API for image URL
     */
    public function searchPexels(string $query): ?string
    {
        if (!$this->pexelsApiKey) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->pexelsApiKey,
            ])->get('https://api.pexels.com/v1/search', [
                'query' => $query,
                'per_page' => 1,
            ]);

            if ($response->successful() && $response['photos']) {
                $photo = $response['photos'][0];
                return $photo['src']['original'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            logger('Pexels API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download image from Pexels and create local icon
     */
    private function downloadAndCreateIcon(string $entityType, int $entityId, string $pexelsUrl): ?string
    {
        try {
            $entityPath = "{$this->storagePath}/{$entityType}/{$entityId}";
            @mkdir($entityPath, 0755, true);

            $imageData = Http::get($pexelsUrl)->body();
            $imagePath = "{$entityPath}/original.jpg";
            file_put_contents($imagePath, $imageData);

            $iconPath = "{$entityPath}/icon.jpg";
            $this->resizeImage($imagePath, $iconPath, 60, 60);

            return "/images/entities/{$entityType}/{$entityId}/icon.jpg";
        } catch (\Exception $e) {
            logger('Image download error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Resize image using GD library
     */
    private function resizeImage(string $source, string $destination, int $width, int $height): void
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD library not available');
        }

        $image = \imagecreatefromjpeg($source);
        if (!$image) {
            throw new \Exception('Failed to create image from JPEG');
        }

        $resized = \imagecreatetruecolor($width, $height);
        if (!$resized) {
            \imagedestroy($image);
            throw new \Exception('Failed to create true color image');
        }

        \imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, \imagesx($image), \imagesy($image));

        $jpegResult = \imagejpeg($resized, $destination, 85);
        if (!$jpegResult) {
            \imagedestroy($image);
            \imagedestroy($resized);
            throw new \Exception("Failed to write JPEG to {$destination}");
        }

        \imagedestroy($image);
        \imagedestroy($resized);
    }
}

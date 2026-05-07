<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ImageFetcher
{
    private string $pexelsApiKey;

    public function __construct()
    {
        $this->pexelsApiKey = config('services.pexels.key') ?? '';
    }

    /**
     * Fetch image for entity from Pexels API
     */
    public function fetchForEntity(string $entityType, int $entityId, string $name): ?string
    {
        return $this->searchPexels($name);
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
}

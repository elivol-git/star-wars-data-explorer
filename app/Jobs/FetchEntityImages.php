<?php

namespace App\Jobs;

use App\Models\EntityImage;
use App\Services\ImageFetcher;
use App\Services\PexelsRateLimited;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchEntityImages implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $entityType,
        private int $entityId,
        private string $entityName,
    ) {}

    // Pexels free tier is ~200 requests/hour; keep retrying through rate limits.
    public int $tries = 30;

    public function handle(ImageFetcher $fetcher): void
    {
        $relative = "/images/entities/{$this->entityType}/{$this->entityId}/icon.jpg";
        $key = ['entity_type' => $this->entityType, 'entity_id' => $this->entityId];

        // Already downloaded: just make sure the record points at it, don't spend API quota.
        if (is_file(public_path($relative))) {
            EntityImage::updateOrCreate($key, [
                'image_url' => $relative,
                'source' => 'pexels',
                'fetched_at' => now(),
            ]);
            return;
        }

        try {
            $imageUrl = $fetcher->fetchForEntity($this->entityType, $this->entityId, $this->entityName);
        } catch (PexelsRateLimited) {
            $this->release(random_int(300, 900));
            return;
        }

        EntityImage::updateOrCreate(
            [
                'entity_type' => $this->entityType,
                'entity_id' => $this->entityId,
            ],
            [
                'image_url' => $imageUrl,
                'source' => 'pexels',
                'fetched_at' => now(),
            ]
        );
    }
}

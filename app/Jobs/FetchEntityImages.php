<?php

namespace App\Jobs;

use App\Models\EntityImage;
use App\Services\ImageFetcher;
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

    public function handle(ImageFetcher $fetcher): void
    {
        $imageUrl = $fetcher->fetchForEntity($this->entityType, $this->entityId, $this->entityName);

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

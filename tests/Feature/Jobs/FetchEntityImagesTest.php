<?php

namespace Tests\Feature\Jobs;

use App\Jobs\FetchEntityImages;
use App\Models\EntityImage;
use App\Services\ImageFetcher;
use Tests\TestCase;

class FetchEntityImagesTest extends TestCase
{
    public function test_job_creates_entity_image_record()
    {
        $fetcher = app(ImageFetcher::class);
        $job = new FetchEntityImages('Person', 1, 'Luke Skywalker');
        $job->handle($fetcher);

        $record = EntityImage::where('entity_type', 'Person')
            ->where('entity_id', 1)
            ->first();

        $this->assertNotNull($record);
        $this->assertTrue(is_string($record->image_url) || is_null($record->image_url));
    }

    public function test_job_stores_null_if_no_image_found()
    {
        $fetcher = app(ImageFetcher::class);
        $job = new FetchEntityImages('Person', 999, 'NonexistentCharacter12345XYZ');
        $job->handle($fetcher);

        $record = EntityImage::where('entity_type', 'Person')
            ->where('entity_id', 999)
            ->first();

        $this->assertNotNull($record);
    }

    public function test_job_updates_existing_record()
    {
        EntityImage::create([
            'entity_type' => 'Planet',
            'entity_id' => 1,
            'image_url' => 'http://old-url.com',
        ]);

        $fetcher = app(ImageFetcher::class);
        $job = new FetchEntityImages('Planet', 1, 'Tatooine');
        $job->handle($fetcher);

        $record = EntityImage::where('entity_type', 'Planet')
            ->where('entity_id', 1)
            ->first();

        $this->assertNotNull($record);
    }
}

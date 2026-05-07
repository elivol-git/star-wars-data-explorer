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
        // Mock fetcher to return a URL
        $this->mock(ImageFetcher::class, function ($mock) {
            $mock->shouldReceive('fetchForEntity')
                ->once()
                ->with('Person', 1, 'Luke Skywalker')
                ->andReturn('https://images.pexels.com/luke.jpg');
        });

        $job = new FetchEntityImages('Person', 1, 'Luke Skywalker');
        $job->handle(app(ImageFetcher::class));

        $this->assertDatabaseHas('entity_images', [
            'entity_type' => 'Person',
            'entity_id' => 1,
            'image_url' => 'https://images.pexels.com/luke.jpg',
            'source' => 'pexels',
        ]);
    }

    public function test_job_stores_null_if_no_image_found()
    {
        // Mock fetcher to return null
        $this->mock(ImageFetcher::class, function ($mock) {
            $mock->shouldReceive('fetchForEntity')
                ->once()
                ->andReturn(null);
        });

        $job = new FetchEntityImages('Person', 999, 'NonexistentCharacter');
        $job->handle(app(ImageFetcher::class));

        $this->assertDatabaseHas('entity_images', [
            'entity_type' => 'Person',
            'entity_id' => 999,
            'image_url' => null,
        ]);
    }

    public function test_job_updates_existing_record()
    {
        EntityImage::create([
            'entity_type' => 'Planet',
            'entity_id' => 1,
            'image_url' => 'http://old-url.com',
        ]);

        // Mock fetcher to return new URL
        $this->mock(ImageFetcher::class, function ($mock) {
            $mock->shouldReceive('fetchForEntity')
                ->once()
                ->andReturn('https://images.pexels.com/tatooine.jpg');
        });

        $job = new FetchEntityImages('Planet', 1, 'Tatooine');
        $job->handle(app(ImageFetcher::class));

        $this->assertDatabaseHas('entity_images', [
            'entity_type' => 'Planet',
            'entity_id' => 1,
            'image_url' => 'https://images.pexels.com/tatooine.jpg',
        ]);

        // Verify it updated, not created new
        $this->assertDatabaseCount('entity_images', 1);
    }

    public function test_job_sets_source_and_fetched_at()
    {
        $this->mock(ImageFetcher::class, function ($mock) {
            $mock->shouldReceive('fetchForEntity')
                ->andReturn('https://images.pexels.com/film.jpg');
        });

        $job = new FetchEntityImages('Film', 1, 'A New Hope');
        $job->handle(app(ImageFetcher::class));

        $record = EntityImage::where('entity_type', 'Film')
            ->where('entity_id', 1)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('pexels', $record->source);
        $this->assertNotNull($record->fetched_at);
    }
}

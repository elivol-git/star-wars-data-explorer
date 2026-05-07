<?php

namespace Tests\Unit\Services;

use App\Services\ImageFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImageFetcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set a dummy API key for tests
        config(['services.pexels.key' => 'test-api-key']);
    }

    public function test_fetch_for_entity_returns_url_string()
    {
        Http::fake([
            'api.pexels.com/*' => Http::response([
                'photos' => [
                    [
                        'src' => ['original' => 'https://example.com/image.jpg']
                    ]
                ]
            ], 200),
        ]);

        $fetcher = new ImageFetcher();
        $result = $fetcher->fetchForEntity('Person', 1, 'Luke Skywalker');

        $this->assertIsString($result);
        $this->assertEquals('https://example.com/image.jpg', $result);
    }

    public function test_search_pexels_returns_null_when_no_photos()
    {
        Http::fake([
            'api.pexels.com/*' => Http::response([
                'photos' => []
            ], 200),
        ]);

        $fetcher = new ImageFetcher();
        $result = $fetcher->searchPexels('NonexistentEntity123XYZ');

        $this->assertNull($result);
    }

    public function test_search_pexels_handles_api_error()
    {
        Http::fake([
            'api.pexels.com/*' => Http::response([], 429), // Rate limited
        ]);

        $fetcher = new ImageFetcher();
        $result = $fetcher->searchPexels('Luke Skywalker');

        $this->assertNull($result);
    }

    public function test_returns_null_without_api_key()
    {
        // Override the setUp() configuration for this specific test
        config(['services.pexels.key' => null]);

        $fetcher = new ImageFetcher();
        $result = $fetcher->searchPexels('Luke Skywalker');

        $this->assertNull($result);
    }

    public function test_extracts_image_url_correctly()
    {
        Http::fake([
            'api.pexels.com/*' => Http::response([
                'photos' => [
                    [
                        'src' => [
                            'original' => 'https://images.pexels.com/original.jpg',
                            'large' => 'https://images.pexels.com/large.jpg',
                        ]
                    ]
                ]
            ], 200),
        ]);

        $fetcher = new ImageFetcher();
        $result = $fetcher->searchPexels('Tatooine');

        $this->assertEquals('https://images.pexels.com/original.jpg', $result);
    }
}

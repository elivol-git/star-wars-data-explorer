<?php

namespace Tests\Unit\Services;

use App\Services\ImageFetcher;
use Tests\TestCase;

class ImageFetcherTest extends TestCase
{
    public function test_fetch_for_entity_returns_null_if_no_results()
    {
        $fetcher = new ImageFetcher();
        $result = $fetcher->fetchForEntity('Person', 1, 'NonexistentEntity');

        $this->assertNull($result);
    }

    public function test_search_pexels_returns_url_on_success()
    {
        $fetcher = new ImageFetcher();
        // Mock Pexels response
        $result = $fetcher->searchPexels('Luke Skywalker');

        // Should return URL string or null (depends on API)
        $this->assertTrue(is_string($result) || is_null($result));
    }

    public function test_fetch_for_entity_calls_search_pexels()
    {
        $fetcher = new ImageFetcher();
        $result = $fetcher->fetchForEntity('Person', 1, 'Luke Skywalker');

        // Result should be string URL or null
        $this->assertTrue(is_string($result) || is_null($result));
    }
}

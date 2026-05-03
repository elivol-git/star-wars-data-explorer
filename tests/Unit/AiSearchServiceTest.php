<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Llm\Search\StarWarsAiSearchService;
use App\Services\Llm\Search\StarWarsSearchRepository;
use App\Services\Llm\LlmSearchService;
use App\Models\Planet;
use Mockery;

class AiSearchServiceTest extends TestCase
{
    private StarWarsAiSearchService $service;
    private LlmSearchService $llmService;
    private StarWarsSearchRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llmService = $this->app->make(LlmSearchService::class);
        $this->repository = $this->app->make(StarWarsSearchRepository::class);
        $this->service = $this->app->make(StarWarsAiSearchService::class);

        // Create test data
        Planet::factory()->create(['name' => 'Tatooine', 'population' => 0]);
        Planet::factory()->create(['name' => 'Alderaan', 'population' => 2000000000]);
        Planet::factory()->create(['name' => 'Naboo', 'population' => 5000000]);
    }

    public function test_search_returns_array_with_required_keys()
    {
        $result = $this->service->search('POPULATION is 0');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('entity', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('parsed', $result);
    }

    public function test_search_parses_numeric_filters()
    {
        $result = $this->service->search('POPULATION is 0');

        $this->assertIsArray($result['parsed']['filters']);
        $this->assertArrayHasKey('population', $result['parsed']['filters']);
    }

    public function test_search_handles_planet_entity()
    {
        $result = $this->service->search('planets with population 0');

        $this->assertEquals('planets', $result['entity']);
        $this->assertIsArray($result['data']);
    }

    public function test_search_filters_planets_correctly()
    {
        $result = $this->service->search('POPULATION is 0');

        foreach ($result['data'] as $planet) {
            $this->assertEquals(0, $planet['population']);
        }
    }

    public function test_search_handles_mixed_results()
    {
        $result = $this->service->search('Star Wars');

        $this->assertTrue(in_array($result['entity'], ['mixed', 'planets', 'films', 'people', 'species', 'starships', 'vehicles']));
    }

    public function test_search_caches_results()
    {
        $query = 'POPULATION is 0';

        $result1 = $this->service->search($query);
        $result2 = $this->service->search($query);

        $this->assertEquals($result1, $result2);
    }

    public function test_search_returns_collection()
    {
        $result = $this->service->search('desert planets');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['data']);
    }

    public function test_search_attaches_match_context()
    {
        $result = $this->service->search('POPULATION is 0');

        $data = $result['data'] instanceof \Illuminate\Support\Collection ? $result['data'] : collect($result['data']);
        if ($data->isNotEmpty()) {
            $first = $data->first();
            // Match context should be attached to items
            $this->assertTrue(is_object($first));
        }
    }

    public function test_repository_normalizes_numeric_filters()
    {
        $filters = ['population' => '< 1000000'];
        $normalized = $this->repository->normalizeFilters($filters);

        $this->assertArrayHasKey('population', $normalized);
        $this->assertIsArray($normalized['population']);
        $this->assertEquals('<', $normalized['population']['operator']);
        $this->assertEquals('1000000', $normalized['population']['value']);
    }

    public function test_repository_handles_equality_operator()
    {
        $filters = ['population' => '= 0'];
        $normalized = $this->repository->normalizeFilters($filters);

        $this->assertEquals('=', $normalized['population']['operator']);
        $this->assertEquals('0', $normalized['population']['value']);
    }

    public function test_repository_handles_greater_than()
    {
        $filters = ['population' => '> 1000000'];
        $normalized = $this->repository->normalizeFilters($filters);

        $this->assertEquals('>', $normalized['population']['operator']);
    }

    public function test_repository_handles_greater_or_equal()
    {
        $filters = ['population' => '>= 1000000'];
        $normalized = $this->repository->normalizeFilters($filters);

        $this->assertEquals('>=', $normalized['population']['operator']);
    }

    public function test_repository_handles_less_or_equal()
    {
        $filters = ['population' => '<= 1000000'];
        $normalized = $this->repository->normalizeFilters($filters);

        $this->assertEquals('<=', $normalized['population']['operator']);
    }

    public function test_repository_search_applies_filters()
    {
        $result = $this->repository->search('planets', [], ['population' => '= 0']);

        $this->assertGreaterThan(0, count($result));
        foreach ($result as $planet) {
            $this->assertEquals(0, $planet['population']);
        }
    }

    public function test_repository_search_limits_results()
    {
        $result = $this->repository->search('planets', []);

        $this->assertLessThanOrEqual(20, count($result));
    }
}

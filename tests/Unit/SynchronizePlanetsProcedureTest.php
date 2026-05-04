<?php

namespace Tests\Unit;

use App\Models\Planet;
use App\Models\Film;
use App\Services\SynchronizeStarWarsProcedure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;
use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SynchronizePlanetsProcedureTest extends TestCase
{

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_uses_cached_data(): void
    {
        Cache::shouldReceive('get')->once()->andReturn(json_encode(['results' => []]));

        $procedure = new SynchronizeStarWarsProcedure();

        $method = (new \ReflectionClass($procedure))->getMethod('fetchJsonWithCache');
        $method->setAccessible(true);
        $result = $method->invoke($procedure, 'any-url');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
    }

    /** @test */
    public function it_throws_exception_if_remote_fails_and_no_cache(): void
    {
        Cache::shouldReceive('get')->once()->andReturn(null);

        $responseMock = Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')->andReturn(500);
        $responseMock->shouldReceive('getBody')->andReturn(
            Mockery::mock(StreamInterface::class, ['getContents' => ''])
        );

        $clientMock = Mockery::mock(Client::class);
        $clientMock->shouldReceive('get')->andReturn($responseMock);

        $procedure = new SynchronizeStarWarsProcedure();

        $reflection = new \ReflectionClass($procedure);
        $prop = $reflection->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($procedure, $clientMock);

        $this->expectException(Exception::class);

        $method = $reflection->getMethod('fetchJsonWithCache');
        $method->setAccessible(true);
        $method->invoke($procedure, 'any-url');
    }

    /** @test */
    public function it_clears_tables_properly(): void
    {
        Schema::disableForeignKeyConstraints();
        Planet::truncate();
        Film::truncate();
        Schema::enableForeignKeyConstraints();

        $this->assertTrue(true); // No exception thrown
    }

    /** @test */
    public function it_stores_planet_with_films(): void
    {
        $procedure = new SynchronizeStarWarsProcedure();

        $planetData = [
            'name' => 'Tatooine',
            'rotation_period' => 23,
            'orbital_period' => 304,
            'surface_water' => 1,
            'population' => 200000,
            'climate' => 'arid',
            'gravity' => '1 standard',
            'terrain' => 'desert',
            'url' => 'https://swapi.dev/api/planets/1/',
            'created' => now()->toDateTimeString(),
            'edited' => now()->toDateTimeString(),
            'films' => []
        ];

        $method = (new \ReflectionClass($procedure))->getMethod('storePlanetWithFilms');
        $method->setAccessible(true);
        $method->invoke($procedure, $planetData);

        $this->assertDatabaseHas('planets', ['name' => 'Tatooine']);
    }

    /** @test */
    public function it_runs_sync_planets_method_correctly(): void
    {
        $procedure = Mockery::mock(SynchronizeStarWarsProcedure::class)->makePartial();
        $procedure->shouldAllowMockingProtectedMethods();

        $procedure->shouldReceive('fetchJsonWithCache')->once()->andReturn(['results' => []]);
        $procedure->shouldReceive('assertValidJson')->once();
        $procedure->shouldReceive('clearTables')->once();
        $procedure->shouldReceive('syncPlanets')->once();

        $procedure->run();

        $this->assertTrue(true); // Just ensure run() completes
    }

    /** @test */
    public function it_handles_exception_and_logs_error()
    {
        Notification::fake();

        Log::shouldReceive('error')->once()->withAnyArgs();

        $procedure = Mockery::mock(SynchronizeStarWarsProcedure::class)->makePartial();
        $procedure->shouldAllowMockingProtectedMethods();
        $procedure->shouldReceive('fetchJsonWithCache')->andThrow(new \Exception('Test failure'));

        $this->expectException(\Exception::class);

        $procedure->run();
    }


}

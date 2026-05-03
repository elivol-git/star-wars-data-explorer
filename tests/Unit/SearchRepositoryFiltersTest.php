<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Llm\Search\StarWarsSearchRepository;
use App\Models\Planet;
use App\Models\Person;
use App\Models\Starship;
use App\Models\Vehicle;
use App\Models\Species;

class SearchRepositoryFiltersTest extends TestCase
{
    private StarWarsSearchRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(StarWarsSearchRepository::class);

        // Create test planets
        Planet::factory()->create(['name' => 'Tatooine', 'population' => 0, 'diameter' => 10465, 'orbital_period' => 304.65, 'rotation_period' => 23.93, 'surface_water' => 1, 'climate' => 'arid', 'terrain' => 'desert']);
        Planet::factory()->create(['name' => 'Naboo', 'population' => 5000000, 'diameter' => 12120, 'orbital_period' => 312, 'rotation_period' => 26, 'surface_water' => 28, 'climate' => 'temperate', 'terrain' => 'grassy']);
        Planet::factory()->create(['name' => 'Dagoba', 'population' => null, 'diameter' => 8500, 'orbital_period' => 350, 'rotation_period' => 25, 'surface_water' => 100, 'climate' => 'murky', 'terrain' => 'swamp, jungle']);

        // Create test people
        Person::factory()->create(['name' => 'Luke Skywalker', 'height' => 172, 'mass' => 77]);
        Person::factory()->create(['name' => 'Yoda', 'height' => 66, 'mass' => 17]);

        // Create test starships
        Starship::factory()->create(['name' => 'Millennium Falcon', 'length' => 34.37, 'crew' => '4 or more', 'starship_class' => 'Light transport']);
        Starship::factory()->create(['name' => 'Star Destroyer', 'length' => 1600, 'crew' => '9700', 'starship_class' => 'Star Destroyer']);

        // Create test vehicles
        Vehicle::factory()->create(['name' => 'Sand Crawler', 'length' => 36.8, 'crew' => '46', 'vehicle_class' => 'wheeled']);
        Vehicle::factory()->create(['name' => 'Speeder', 'length' => 5, 'crew' => '1', 'vehicle_class' => 'repulsorcraft']);

        // Create test species
        Species::factory()->create(['name' => 'Human', 'classification' => 'mammal', 'language' => 'Galactic Basic', 'average_lifespan' => 120]);
        Species::factory()->create(['name' => 'Yoda\'s species', 'classification' => 'amphibian', 'language' => 'Galactic Basic', 'average_lifespan' => 900]);
    }

    // PLANET FILTERS
    public function test_filter_planets_by_population_equals()
    {
        $result = $this->repository->search('planets', [], ['population' => '= 0']);

        foreach ($result as $planet) {
            $this->assertEquals(0, $planet['population']);
        }
    }

    public function test_filter_planets_by_population_less_than()
    {
        $result = $this->repository->search('planets', [], ['population' => '< 1000000']);

        foreach ($result as $planet) {
            if ($planet['population'] !== null) {
                $this->assertLessThan(1000000, $planet['population']);
            }
        }
    }

    public function test_filter_planets_by_population_greater_than()
    {
        $result = $this->repository->search('planets', [], ['population' => '> 1000000000']);

        foreach ($result as $planet) {
            if ($planet['population'] !== null) {
                $this->assertGreaterThan(1000000000, $planet['population']);
            }
        }
    }

    public function test_filter_planets_by_diameter()
    {
        $result = $this->repository->search('planets', [], ['diameter' => '> 10000']);

        foreach ($result as $planet) {
            if ($planet['diameter'] !== null) {
                $this->assertGreaterThan(10000, $planet['diameter']);
            }
        }
    }

    public function test_filter_planets_by_orbital_period_less_equal()
    {
        $result = $this->repository->search('planets', [], ['orbital_period' => '<= 365']);

        foreach ($result as $planet) {
            if ($planet['orbital_period'] !== null) {
                $this->assertLessThanOrEqual(365, $planet['orbital_period']);
            }
        }
    }

    public function test_filter_planets_by_rotation_period()
    {
        $result = $this->repository->search('planets', [], ['rotation_period' => '>= 20']);

        foreach ($result as $planet) {
            if ($planet['rotation_period'] !== null) {
                $this->assertGreaterThanOrEqual(20, $planet['rotation_period']);
            }
        }
    }

    public function test_filter_planets_by_surface_water()
    {
        $result = $this->repository->search('planets', [], ['surface_water' => '> 0']);

        foreach ($result as $planet) {
            if ($planet['surface_water'] !== null) {
                $this->assertGreaterThan(0, $planet['surface_water']);
            }
        }
    }

    public function test_filter_planets_by_climate_keyword()
    {
        $result = $this->repository->search('planets', [], ['climate' => 'desert']);

        foreach ($result as $planet) {
            $this->assertStringContainsString('desert', strtolower($planet['climate'] ?? ''));
        }
    }

    public function test_filter_planets_by_terrain_keyword()
    {
        $result = $this->repository->search('planets', [], ['terrain' => 'forest']);

        foreach ($result as $planet) {
            $this->assertStringContainsString('forest', strtolower($planet['terrain'] ?? ''));
        }
    }

    // PEOPLE FILTERS
    public function test_filter_people_by_height()
    {
        $result = $this->repository->search('people', [], ['height' => '> 180']);

        $this->assertGreaterThan(0, count($result));
        foreach ($result as $person) {
            if ($person['height'] !== null && $person['height'] !== 'unknown') {
                $this->assertGreaterThan(180, (int)$person['height']);
            }
        }
    }

    public function test_filter_people_by_mass()
    {
        $result = $this->repository->search('people', [], ['mass' => '< 100']);

        $this->assertGreaterThan(0, count($result));
    }

    public function test_filter_people_by_name_keyword()
    {
        $result = $this->repository->search('people', [], ['name' => 'Luke']);

        foreach ($result as $person) {
            $this->assertStringContainsString('Luke', $person['name']);
        }
    }

    // STARSHIP FILTERS
    public function test_filter_starships_by_length()
    {
        $result = $this->repository->search('starships', [], ['length' => '> 100']);

        foreach ($result as $ship) {
            if ($ship['length'] !== null) {
                $this->assertGreaterThan(100, (float)$ship['length']);
            }
        }
    }

    public function test_filter_starships_by_crew()
    {
        $result = $this->repository->search('starships', [], ['crew' => '> 1000']);

        foreach ($result as $ship) {
            if ($ship['crew'] !== null && is_numeric($ship['crew'])) {
                $this->assertGreaterThan(1000, (int)$ship['crew']);
            }
        }
    }

    public function test_filter_starships_by_class_keyword()
    {
        $result = $this->repository->search('starships', [], ['starship_class' => 'Star Destroyer']);

        foreach ($result as $ship) {
            if (isset($ship['starship_class'])) {
                $this->assertStringContainsString('Star Destroyer', $ship['starship_class']);
            }
        }
    }

    // VEHICLE FILTERS
    public function test_filter_vehicles_by_length()
    {
        $result = $this->repository->search('vehicles', [], ['length' => '< 20']);

        foreach ($result as $vehicle) {
            if ($vehicle['length'] !== null) {
                $this->assertLessThan(20, (float)$vehicle['length']);
            }
        }
    }

    public function test_filter_vehicles_by_crew()
    {
        $result = $this->repository->search('vehicles', [], ['crew' => '= 1']);

        foreach ($result as $vehicle) {
            if ($vehicle['crew'] !== null && is_numeric($vehicle['crew'])) {
                $this->assertEquals(1, (int)$vehicle['crew']);
            }
        }
    }

    public function test_filter_vehicles_by_class_keyword()
    {
        $result = $this->repository->search('vehicles', [], ['vehicle_class' => 'wheeled']);

        foreach ($result as $vehicle) {
            if (isset($vehicle['vehicle_class'])) {
                $this->assertStringContainsString('wheeled', strtolower($vehicle['vehicle_class']));
            }
        }
    }

    // SPECIES FILTERS
    public function test_filter_species_by_classification()
    {
        $result = $this->repository->search('species', [], ['classification' => 'humanoid']);

        foreach ($result as $species) {
            $this->assertStringContainsString('humanoid', strtolower($species['classification'] ?? ''));
        }
    }

    public function test_filter_species_by_language()
    {
        $result = $this->repository->search('species', [], ['language' => 'Basic']);

        foreach ($result as $species) {
            if ($species['language'] ?? null) {
                $this->assertStringContainsString('Basic', $species['language']);
            }
        }
    }

    public function test_filter_species_by_lifespan()
    {
        $result = $this->repository->search('species', [], ['average_lifespan' => '> 100']);

        foreach ($result as $species) {
            if ($species['average_lifespan'] !== null && is_numeric($species['average_lifespan'])) {
                $this->assertGreaterThan(100, (int)$species['average_lifespan']);
            }
        }
    }

    // COMPLEX FILTER SCENARIOS
    public function test_multiple_numeric_operators_parsed_correctly()
    {
        $testCases = [
            '= 0' => ['operator' => '=', 'value' => '0'],
            '< 1000' => ['operator' => '<', 'value' => '1000'],
            '> 1000000' => ['operator' => '>', 'value' => '1000000'],
            '<= 500' => ['operator' => '<=', 'value' => '500'],
            '>= 1000' => ['operator' => '>=', 'value' => '1000'],
        ];

        foreach ($testCases as $input => $expected) {
            $normalized = $this->repository->normalizeFilters(['test' => $input]);
            $this->assertEquals($expected['operator'], $normalized['test']['operator']);
            $this->assertEquals($expected['value'], $normalized['test']['value']);
        }
    }

    public function test_string_filters_not_parsed_as_numeric()
    {
        $normalized = $this->repository->normalizeFilters(['name' => 'Luke Skywalker']);

        $this->assertIsString($normalized['name']);
        $this->assertEquals('Luke Skywalker', $normalized['name']);
    }

    public function test_results_respect_limit()
    {
        $result = $this->repository->search('planets', [], []);

        $this->assertLessThanOrEqual(20, count($result));
    }
}

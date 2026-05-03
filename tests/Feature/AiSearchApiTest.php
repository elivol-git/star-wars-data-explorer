<?php

namespace Tests\Feature;

use Tests\TestCase;

class AiSearchApiTest extends TestCase
{
    public function test_search_planet_by_population_equals()
    {
        $response = $this->get('/api/ai-search?q=POPULATION+is+0');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'entity',
                     'data' => ['*' => ['id', 'name', 'population']]
                 ]);

        $data = $response->json();
        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));

        foreach ($data['data'] as $planet) {
            $this->assertEquals(0, $planet['population']);
        }
    }

    public function test_search_planet_by_population_less_than()
    {
        $response = $this->get('/api/ai-search?q=POPULATION+less+than+1000000');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
        foreach ($data['data'] as $planet) {
            $this->assertLessThan(1000000, $planet['population'] ?? 0);
        }
    }

    public function test_search_planet_by_population_greater_than()
    {
        $response = $this->get('/api/ai-search?q=POPULATION+greater+than+1000000000');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
        foreach ($data['data'] as $planet) {
            if ($planet['population'] !== null) {
                $this->assertGreaterThan(1000000000, $planet['population']);
            }
        }
    }

    public function test_search_planet_by_diameter()
    {
        $response = $this->get('/api/ai-search?q=DIAMETER+greater+than+10000');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
        foreach ($data['data'] as $planet) {
            if ($planet['diameter'] !== null) {
                $this->assertGreaterThan(10000, $planet['diameter']);
            }
        }
    }

    public function test_search_planet_by_orbital_period()
    {
        $response = $this->get('/api/ai-search?q=ORBITAL+PERIOD+less+than+500');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
        foreach ($data['data'] as $planet) {
            if ($planet['orbital_period'] !== null) {
                $this->assertLessThan(500, $planet['orbital_period']);
            }
        }
    }

    public function test_search_planet_by_climate()
    {
        $response = $this->get('/api/ai-search?q=desert+planets');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
        // May return mixed or no results depending on LLM parsing
    }

    public function test_search_planet_by_terrain()
    {
        $response = $this->get('/api/ai-search?q=planets+with+forest+terrain');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
    }

    public function test_search_by_film_title()
    {
        $response = $this->get('/api/ai-search?q=planets+in+A+New+Hope');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
    }

    public function test_search_by_resident_name()
    {
        $response = $this->get('/api/ai-search?q=planets+where+Luke+Skywalker+lives');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
    }

    public function test_search_starships_by_name()
    {
        $response = $this->get('/api/ai-search?q=Millennium+Falcon');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['starships', 'mixed']));
    }

    public function test_search_vehicles_by_name()
    {
        $response = $this->get('/api/ai-search?q=Sand+Crawler');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['vehicles', 'mixed']));
    }

    public function test_search_species()
    {
        $response = $this->get('/api/ai-search?q=Wookiee');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['species', 'mixed']));
    }

    public function test_search_people_by_name()
    {
        $response = $this->get('/api/ai-search?q=Darth+Vader');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['people', 'mixed', 'planets']));
    }

    public function test_search_missing_query_returns_error()
    {
        $response = $this->get('/api/ai-search');

        $response->assertStatus(400)
                 ->assertJsonStructure(['error']);
    }

    public function test_search_response_has_parsed_data()
    {
        $response = $this->get('/api/ai-search?q=POPULATION+equals+0');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('parsed', $data);
        $this->assertArrayHasKey('entity', $data['parsed']);
        $this->assertArrayHasKey('filters', $data['parsed']);
        $this->assertArrayHasKey('keywords', $data['parsed']);
    }

    public function test_search_planets_with_surface_water()
    {
        $response = $this->get('/api/ai-search?q=planets+with+surface+water');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
    }

    public function test_search_by_rotation_period()
    {
        $response = $this->get('/api/ai-search?q=rotation+period+greater+than+20');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue(in_array($data['entity'], ['planets', 'mixed']));
    }

    public function test_search_films_by_director()
    {
        $response = $this->get('/api/ai-search?q=films+directed+by+George+Lucas');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_returns_correct_data_structure()
    {
        $response = $this->get('/api/ai-search?q=POPULATION+is+0');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('entity', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('parsed', $data);

        if (!empty($data['data'])) {
            $first = $data['data'][0];
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('name', $first);
        }
    }
}

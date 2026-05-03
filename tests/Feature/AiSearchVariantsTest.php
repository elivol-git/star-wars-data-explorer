<?php

namespace Tests\Feature;

use Tests\TestCase;

class AiSearchVariantsTest extends TestCase
{
    // PLANET PROPERTY SEARCHES
    public function test_search_planet_surface_water_less_than()
    {
        $response = $this->get('/api/ai-search?q=planets+with+surface+water+less+than+50');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('planets', $data['entity']);
    }

    public function test_search_planet_gravity()
    {
        $response = $this->get('/api/ai-search?q=high+gravity+planets');

        $response->assertStatus(200);
        $data = $response->json();
    }

    // FILM PROPERTY SEARCHES
    public function test_search_films_by_release_date()
    {
        $response = $this->get('/api/ai-search?q=films+released+after+2000');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_films_by_episode()
    {
        $response = $this->get('/api/ai-search?q=episode+4');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_films_with_character()
    {
        $response = $this->get('/api/ai-search?q=films+with+Yoda');

        $response->assertStatus(200);
        $data = $response->json();
    }

    // RESIDENT/PEOPLE PROPERTY SEARCHES
    public function test_search_people_by_height()
    {
        $response = $this->get('/api/ai-search?q=people+taller+than+200');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_people_by_mass()
    {
        $response = $this->get('/api/ai-search?q=people+lighter+than+80');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_people_by_birth_year()
    {
        $response = $this->get('/api/ai-search?q=people+born+before+year+0');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_people_by_gender()
    {
        $response = $this->get('/api/ai-search?q=female+characters');

        $response->assertStatus(200);
        $data = $response->json();
    }

    // STARSHIP SEARCHES
    public function test_search_starship_by_class()
    {
        $response = $this->get('/api/ai-search?q=star+destroyers');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_starship_by_manufacturer()
    {
        $response = $this->get('/api/ai-search?q=starships+made+by+Sienar');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_starship_by_length()
    {
        $response = $this->get('/api/ai-search?q=starships+longer+than+100');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_starship_by_crew()
    {
        $response = $this->get('/api/ai-search?q=starships+with+crew+larger+than+1000');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_starship_by_cost()
    {
        $response = $this->get('/api/ai-search?q=expensive+starships');

        $response->assertStatus(200);
        $data = $response->json();
    }

    // VEHICLE SEARCHES
    public function test_search_vehicle_by_class()
    {
        $response = $this->get('/api/ai-search?q=wheeled+vehicles');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_vehicle_by_length()
    {
        $response = $this->get('/api/ai-search?q=vehicles+shorter+than+10');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_vehicle_by_speed()
    {
        $response = $this->get('/api/ai-search?q=fast+vehicles');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_vehicle_by_crew()
    {
        $response = $this->get('/api/ai-search?q=vehicles+with+crew+less+than+5');

        $response->assertStatus(200);
        $data = $response->json();
    }

    // SPECIES SEARCHES
    public function test_search_species_by_classification()
    {
        $response = $this->get('/api/ai-search?q=humanoid+species');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_species_by_language()
    {
        $response = $this->get('/api/ai-search?q=species+that+speak+Basic');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_species_by_lifespan()
    {
        $response = $this->get('/api/ai-search?q=long+lived+species');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_species_by_average_height()
    {
        $response = $this->get('/api/ai-search?q=tall+species');

        $response->assertStatus(200);
        $data = $response->json();
    }

    // COMPLEX QUERIES
    public function test_search_planets_with_film_and_character()
    {
        $response = $this->get('/api/ai-search?q=planets+where+Luke+appears+in+Return+of+the+Jedi');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_starships_used_in_film()
    {
        $response = $this->get('/api/ai-search?q=starships+in+The+Empire+Strikes+Back');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_vehicles_piloted_by_character()
    {
        $response = $this->get('/api/ai-search?q=vehicles+Han+Solo+piloted');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_species_from_planet()
    {
        $response = $this->get('/api/ai-search?q=species+from+Tatooine');

        $response->assertStatus(200);
        $data = $response->json();
    }

    public function test_search_uses_all_comparison_operators()
    {
        $operators = [
            ['=', 'POPULATION+is+0'],
            ['<', 'POPULATION+less+than+1000'],
            ['>', 'POPULATION+greater+than+1000000'],
            ['<=', 'POPULATION+at+most+1000000'],
            ['>=', 'POPULATION+at+least+1000000'],
        ];

        foreach ($operators as [$op, $query]) {
            $response = $this->get("/api/ai-search?q=$query");
            $response->assertStatus(200);
            $data = $response->json();
            $this->assertIsArray($data['data']);
        }
    }

    public function test_search_returns_entities_with_relationships()
    {
        $response = $this->get('/api/ai-search?q=planets+in+Star+Wars+films');

        $response->assertStatus(200);
        $data = $response->json();

        if (!empty($data['data'])) {
            foreach ($data['data'] as $planet) {
                $this->assertArrayHasKey('films', $planet);
            }
        }
    }
}

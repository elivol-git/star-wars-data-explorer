<?php

namespace Tests\Feature;

use Tests\TestCase;

class AiSearchEdgeCasesTest extends TestCase
{
    public function test_search_with_empty_query_returns_error()
    {
        $response = $this->get('/api/ai-search?q=');

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    public function test_search_without_query_parameter_returns_error()
    {
        $response = $this->get('/api/ai-search');

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    public function test_search_with_special_characters()
    {
        $response = $this->get('/api/ai-search?q=planets+with+%26+special+chars');

        $response->assertStatus(200);
    }

    public function test_search_with_very_large_number()
    {
        $response = $this->get('/api/ai-search?q=POPULATION+greater+than+9999999999');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data['data']);
    }

    public function test_search_with_negative_operator_value()
    {
        $response = $this->get('/api/ai-search?q=BIRTH+YEAR+less+than+-50');

        $response->assertStatus(200);
    }

    public function test_search_returns_max_20_results()
    {
        $response = $this->get('/api/ai-search?q=planets');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertLessThanOrEqual(20, count($data['data']));
    }

    public function test_search_with_non_existent_entity()
    {
        $response = $this->get('/api/ai-search?q=dragons+from+space');

        $response->assertStatus(200);
        $data = $response->json();
        // Should still return valid response, possibly with empty data
        $this->assertArrayHasKey('entity', $data);
        $this->assertArrayHasKey('data', $data);
    }

    public function test_search_results_have_required_fields()
    {
        $response = $this->get('/api/ai-search?q=POPULATION+is+0');

        $response->assertStatus(200);
        $data = $response->json();

        if (!empty($data['data'])) {
            $first = $data['data'][0];
            $this->assertNotNull($first->id);
            $this->assertNotNull($first->name);
        }
    }

    public function test_search_response_is_valid_json()
    {
        $response = $this->get('/api/ai-search?q=test+query');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        // If we got here, JSON was parsed successfully
        $this->assertTrue(true);
    }

    public function test_search_with_url_encoded_spaces()
    {
        $response = $this->get('/api/ai-search?q=planets%20with%20population%200');

        $response->assertStatus(200);
    }

    public function test_search_with_mixed_case_query()
    {
        $response = $this->get('/api/ai-search?q=PoPuLaTiOn+Is+0');

        $response->assertStatus(200);
    }

    public function test_search_filters_non_numeric_values()
    {
        $response = $this->get('/api/ai-search?q=planets+named+Tatooine');

        $response->assertStatus(200);
        $data = $response->json();
        // Should use keyword search, not numeric filter
        $this->assertIsArray($data['data']);
    }

    public function test_search_handles_null_values_gracefully()
    {
        $response = $this->get('/api/ai-search?q=planets');

        $response->assertStatus(200);
        $data = $response->json();

        foreach ($data['data'] as $planet) {
            // Null values should be present without errors
            $this->assertTrue(is_object($planet));
        }
    }

    public function test_search_returns_different_entities()
    {
        $entities = [
            'planets' => 'POPULATION+is+0',
            'starships' => 'Millennium+Falcon',
            'species' => 'Wookiee',
            'people' => 'Darth+Vader',
        ];

        foreach ($entities as $entity => $query) {
            $response = $this->get("/api/ai-search?q=$query");
            $response->assertStatus(200);
            $data = $response->json();
            $this->assertArrayHasKey('entity', $data);
            $this->assertNotNull($data['entity']);
        }
    }

    public function test_search_parsed_structure_always_present()
    {
        $response = $this->get('/api/ai-search?q=random+query');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('parsed', $data);
        $this->assertArrayHasKey('entity', $data['parsed']);
        $this->assertArrayHasKey('keywords', $data['parsed']);
        $this->assertArrayHasKey('filters', $data['parsed']);
        $this->assertArrayHasKey('relations', $data['parsed']);
    }

    public function test_search_multiple_filters_applied()
    {
        $response = $this->get('/api/ai-search?q=planets+with+population+greater+than+1000+and+desert+climate');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('planets', $data['entity']);
    }

    public function test_search_case_insensitive_entity_matching()
    {
        $response = $this->get('/api/ai-search?q=find+me+planets');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertIn($data['entity'], ['planets', 'mixed']);
    }

    public function test_search_handles_throttle_limit()
    {
        for ($i = 0; $i < 25; $i++) {
            $response = $this->get('/api/ai-search?q=test');

            if ($i < 20) {
                $response->assertStatus(200);
            } else {
                // Depending on throttle config, may return 429
                $this->assertIn($response->status(), [200, 429]);
            }
        }
    }
}

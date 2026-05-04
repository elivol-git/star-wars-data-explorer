<?php

namespace App\Services\Llm;

use App\Services\Llm\Clients\LlmClientInterface;
use Illuminate\Support\Facades\Log;

class LlmSearchService
{
    public function __construct(
        private LlmClientInterface $client
    ) {}

    public function parseQuery(string $query): array
    {
        try {
            $response = $this->client->chat(
                [
                    [
                        "role" => "system",
                        "content" => $this->systemPrompt(),
                    ],
                    [
                        "role" => "user",
                        "content" => $query,
                    ],
                ],
                [
                    "temperature" => 0,
                    "top_p" => 1,
                    "max_tokens" => 400,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('LLM unavailable, fallback parser used', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallback($query);
        }

        $content = data_get($response, 'choices.0.message.content');

        if (!$content) {
            return $this->fallback($query);
        }

        Log::info("Raw LLM response", ['response' => $content]);

        $parsed = $this->extractJson($content);

        // Post-process: extract filters from keywords
        return $this->extractFiltersFromKeywords($parsed);
    }

    private function extractJson(string $content): array
    {
        preg_match('/\{.*\}/s', $content, $matches);

        if (!isset($matches[0])) {
            return $this->fallback($content);
        }

        $decoded = json_decode($matches[0], true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            Log::warning("Invalid JSON from LLM", [
                'error' => json_last_error_msg(),
                'content' => $content
            ]);

            return $this->fallback($content);
        }

        return [
            "entity" => $this->validateEntity(
                strtolower($decoded["entity"] ?? "mixed")
            ),

            "keywords" => is_array($decoded["keywords"] ?? null)
                ? $decoded["keywords"]
                : [],

            "filters" => is_array($decoded["filters"] ?? null)
                ? $decoded["filters"]
                : [],

            "relations" => is_array($decoded["relations"] ?? null)
                ? $decoded["relations"]
                : [],

            /*
            |--------------------------------------------------------------------------
            | MATCH (IMPORTANT FIX)
            |--------------------------------------------------------------------------
            */

            "match" => is_array($decoded["match"] ?? null)
                ? $decoded["match"]
                : [],
        ];
    }

    private function validateEntity(string $entity): string
    {
        $allowed = [
            'planets',
            'films',
            'people',
            'species',
            'starships',
            'vehicles',
            'mixed'
        ];

        return in_array($entity, $allowed, true)
            ? $entity
            : 'mixed';
    }

    private function extractFiltersFromKeywords(array $parsed): array
    {
        $numericFields = ['population', 'diameter', 'rotation_period', 'orbital_period', 'height', 'mass', 'cost_in_credits', 'length', 'crew', 'passengers', 'cargo_capacity', 'average_height', 'average_lifespan', 'hyperdrive_rating'];

        $keywords = $parsed['keywords'] ?? [];
        $filters = $parsed['filters'] ?? [];
        $cleanedKeywords = [];

        // Check if any keyword contains a numeric filter pattern
        foreach ($keywords as $keyword) {
            $words = preg_split('/\s+/', trim($keyword), -1, PREG_SPLIT_NO_EMPTY);
            $filterFound = false;

            // Simple check: if keyword contains a numeric field, assume it has a filter
            foreach ($words as $word) {
                if (in_array(strtolower($word), $numericFields, true)) {
                    // This keyword has a numeric field, so it likely contains a filter
                    // Don't add it to cleaned keywords
                    $filterFound = true;
                    break;
                }
            }

            // Only keep keyword if it doesn't contain a numeric field
            if (!$filterFound) {
                $cleanedKeywords[] = $keyword;
            }
        }

        $parsed['keywords'] = $cleanedKeywords;
        $parsed['filters'] = $filters;

        return $parsed;
    }

    private function fallback(string $text): array
    {
        Log::warning("Fallback parser used", ['text' => $text]);

        $entityMap = [
            'planet' => 'planets',
            'planets' => 'planets',
            'film' => 'films',
            'films' => 'films',
            'person' => 'people',
            'people' => 'people',
            'specie' => 'species',
            'species' => 'species',
            'starship' => 'starships',
            'starships' => 'starships',
            'vehicle' => 'vehicles',
            'vehicles' => 'vehicles',
        ];

        $numericFields = ['population', 'diameter', 'rotation_period', 'orbital_period', 'height', 'mass', 'cost_in_credits', 'length', 'crew', 'passengers', 'cargo_capacity', 'average_height', 'average_lifespan', 'hyperdrive_rating'];

        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $filters = [];

        // Detect numeric filters: "property operator value"
        $i = 0;
        while ($i < count($words)) {
            $word = strtolower($words[$i]);
            if (in_array($word, $numericFields, true)) {
                // Found a numeric field, look for operator and value
                if (!isset($words[$i + 1])) {
                    $i++;
                    continue;
                }

                $operator = strtolower($words[$i + 1]);

                // "is" operator (3 words: field, is, value)
                if ($operator === 'is' && isset($words[$i + 2])) {
                    $filters[$word] = '= ' . $words[$i + 2];
                    array_splice($words, $i, 3);
                    continue;
                }

                // Other operators need 4 words minimum
                if (isset($words[$i + 3])) {
                    // Check for "less than", "greater than", etc.
                    $connector = strtolower($words[$i + 2]);

                    // Less than operators
                    if (in_array($operator, ['less', 'smaller'], true) && $connector === 'than') {
                        $filters[$word] = '< ' . $words[$i + 3];
                        array_splice($words, $i, 4);
                        continue;
                    }

                    // Greater than operators
                    if (in_array($operator, ['greater', 'more', 'bigger'], true) && $connector === 'than') {
                        $filters[$word] = '> ' . $words[$i + 3];
                        array_splice($words, $i, 4);
                        continue;
                    }

                    // Equal operators: "equal to", "equals to"
                    if (in_array($operator, ['equal', 'equals'], true) && $connector === 'to') {
                        $filters[$word] = '= ' . $words[$i + 3];
                        array_splice($words, $i, 4);
                        continue;
                    }
                }
            }
            $i++;
        }

        // Remaining words are keywords or entity
        $keywords = [];
        $entity = 'mixed';

        if (count($words) > 0) {
            $firstWord = strtolower($words[0]);
            if (isset($entityMap[$firstWord])) {
                $entity = $entityMap[$firstWord];
                $keywords = array_slice($words, 1);
            } else {
                $keywords = $words;
            }
        }

        // Infer entity from filter field if entity is still mixed
        if ($entity === 'mixed' && !empty($filters)) {
            $filterField = array_key_first($filters);
            $fieldToEntity = [
                'population' => 'planets',
                'diameter' => 'planets',
                'rotation_period' => 'planets',
                'orbital_period' => 'planets',
            ];
            if (isset($fieldToEntity[$filterField])) {
                $entity = $fieldToEntity[$filterField];
            }
        }

        return [
            "entity"    => $entity,
            "keywords"  => $keywords ?: (!empty($filters) ? [] : ($text ? [$text] : [])),
            "filters"   => $filters,
            "relations" => [],
            "match"     => !empty($filters) ? ['property' => array_key_first($filters)] : []
        ];
    }

    private function systemPrompt(): string
    {
        return file_get_contents(
            resource_path('prompts/starwars_search_prompt.txt')
        );
    }
}

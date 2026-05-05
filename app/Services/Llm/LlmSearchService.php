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
        $numericFields = ['population', 'diameter', 'rotation_period', 'orbital_period', 'height', 'mass', 'cost_in_credits', 'length', 'crew', 'passengers', 'cargo_capacity', 'average_height', 'average_lifespan', 'hyperdrive_rating', 'max_atmosphering_speed', 'MGLT'];
        $textFields = ['skin_color', 'hair_color', 'eye_color', 'gender', 'climate', 'terrain', 'gravity', 'classification', 'language', 'designation', 'birth_year', 'name', 'title', 'director', 'producer', 'manufacturer', 'model', 'vehicle_class', 'starship_class', 'consumables', 'skin_colors', 'hair_colors', 'eye_colors'];
        $allFields = array_merge($numericFields, $textFields);

        $keywords = $parsed['keywords'] ?? [];
        $filters = $parsed['filters'] ?? [];
        $cleanedKeywords = [];

        // Check if any keyword contains a filter pattern
        foreach ($keywords as $keyword) {
            $words = preg_split('/\s+/', trim($keyword), -1, PREG_SPLIT_NO_EMPTY);
            $filterFound = false;

            // Simple check: if keyword contains a known field, assume it has a filter
            foreach ($words as $word) {
                if (in_array(strtolower($word), $allFields, true)) {
                    $filterFound = true;
                    break;
                }
                // Check compound: "skin color" → "skin_color"
                // (handled by fallback parser; here we just drop it)
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

        $numericFields = ['population', 'diameter', 'rotation_period', 'orbital_period', 'height', 'mass', 'cost_in_credits', 'length', 'crew', 'passengers', 'cargo_capacity', 'average_height', 'average_lifespan', 'hyperdrive_rating', 'max_atmosphering_speed', 'MGLT'];

        $textFields = ['skin_color', 'hair_color', 'eye_color', 'gender', 'climate', 'terrain', 'gravity', 'classification', 'language', 'designation', 'birth_year', 'name', 'title', 'director', 'producer', 'manufacturer', 'model', 'vehicle_class', 'starship_class', 'consumables', 'skin_colors', 'hair_colors', 'eye_colors'];

        $allFields = array_merge($numericFields, $textFields);

        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $filters = [];

        // Detect numeric filters: "property operator value"
        $i = 0;
        while ($i < count($words)) {
            $start = $i; // track original position for splice
            $word = strtolower($words[$i]);

            // Check for compound fields like "rotation_period" sent as "rotation period"
            // or "max_atmosphering_speed" sent as "max atmosphering speed"
            // Also handle partial matches like "max_atmosphering" for "max_atmosphering_speed"
            $fieldWords = 1; // how many words the field name occupies
            if (isset($words[$i + 2])) {
                $potentialCompound3 = $word . '_' . strtolower($words[$i + 1]) . '_' . strtolower($words[$i + 2]);
                if (in_array($potentialCompound3, $allFields, true)) {
                    $word = $potentialCompound3;
                    $fieldWords = 3;
                    $i += 2; // point to the third word of the compound
                }
            }
            if ($fieldWords === 1 && isset($words[$i + 1])) {
                $potentialCompound = $word . '_' . strtolower($words[$i + 1]);
                if (in_array($potentialCompound, $allFields, true)) {
                    $word = $potentialCompound;
                    $fieldWords = 2;
                    $i++; // point to the second word of the compound
                } else {
                    // Try partial match: "max_atmosphering" might match "max_atmosphering_speed"
                    $partialMatch = null;
                    foreach ($allFields as $field) {
                        if (strpos($field, $potentialCompound) === 0) {
                            $partialMatch = $field;
                            break;
                        }
                    }
                    if ($partialMatch) {
                        $word = $partialMatch;
                        $fieldWords = 2; // mark as processed 2 words
                        $i++;
                    }
                }
            }

            // Text field with "is" operator: "SKIN COLOR is light" or "MODEL is All Terrain Tactical Enforcer"
            if (in_array($word, $textFields, true)) {
                if (isset($words[$i + 1]) && strtolower($words[$i + 1]) === 'is' && isset($words[$i + 2])) {
                    // Consume all remaining words as the value (multi-word product names)
                    $value = implode(' ', array_slice($words, $i + 2));
                    $filters[$word] = $value;
                    array_splice($words, $start);
                    break; // consumed to end
                }
                // Implicit equality: "GENDER female" (no operator)
                if (isset($words[$i + 1])) {
                    $nextWord = strtolower($words[$i + 1]);
                    $excluded = array_merge($allFields, array_keys($entityMap), ['is']);
                    if (!in_array($nextWord, $excluded, true)) {
                        $filters[$word] = $words[$i + 1];
                        array_splice($words, $start, $fieldWords + 1);
                        $i = $start;
                        continue;
                    }
                }
                $i++;
                continue;
            }

            if (in_array($word, $numericFields, true)) {
                // Found a numeric field, look for operator and value
                if (!isset($words[$i + 1])) {
                    $i++;
                    continue;
                }

                $operator = strtolower($words[$i + 1]);

                // "is" operator (field + is + value = fieldWords + 2)
                if ($operator === 'is' && isset($words[$i + 2])) {
                    $filters[$word] = '= ' . $words[$i + 2];
                    array_splice($words, $start, $fieldWords + 2);
                    $i = $start;
                    continue;
                }

                // Other operators need: field + op + connector + value (fieldWords + 3)
                if (isset($words[$i + 3])) {
                    // Check for "less than", "greater than", etc.
                    $connector = strtolower($words[$i + 2]);

                    // Less than operators
                    if (in_array($operator, ['less', 'smaller'], true) && $connector === 'than') {
                        $filters[$word] = '< ' . $words[$i + 3];
                        array_splice($words, $start, $fieldWords + 3);
                        $i = $start;
                        continue;
                    }

                    // Greater than operators
                    if (in_array($operator, ['greater', 'more', 'bigger'], true) && $connector === 'than') {
                        $filters[$word] = '> ' . $words[$i + 3];
                        array_splice($words, $start, $fieldWords + 3);
                        $i = $start;
                        continue;
                    }

                    // Equal operators: "equal to", "equals to"
                    if (in_array($operator, ['equal', 'equals'], true) && $connector === 'to') {
                        $filters[$word] = '= ' . $words[$i + 3];
                        array_splice($words, $start, $fieldWords + 3);
                        $i = $start;
                        continue;
                    }
                }

                // Implicit equality: "CARGO CAPACITY 100000" (field + value, no operator)
                $excluded = array_merge($allFields, array_keys($entityMap), ['is', 'less', 'greater', 'more', 'smaller', 'equal', 'equals', 'than', 'to']);
                if (!in_array($operator, $excluded, true) && is_numeric($operator)) {
                    $filters[$word] = '= ' . $operator;
                    array_splice($words, $start, $fieldWords + 1);
                    $i = $start;
                    continue;
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

        // If still mixed with short query (1-3 words) and no filters, guess entity from keyword pattern
        if ($entity === 'mixed' && empty($filters) && count($keywords) <= 3 && count($keywords) > 0) {
            // Common starship/vehicle/species/people name patterns
            $keywordStr = implode(' ', $keywords);

            // People hints (check first - most specific)
            if (preg_match('/(skywalker|solo|leia|vader|luke|han|lando|chewie|palpatine|maul|binks)/i', $keywordStr)) {
                $entity = 'people';
            }
            // Planet hints
            elseif (preg_match('/(tatooine|alderaan|yavin|hoth|dagobah|bespin|endor|naboo|coruscant|kamino|geonosis|mustafar|kashyyyk)/i', $keywordStr)) {
                $entity = 'planets';
            }
            // Starship hints
            elseif (preg_match('/(falcon|star|wing|fighter|interceptor|cruiser|shuttle|destroyer|executor|slave|barge|republic|xwing|awing|ywing|cls|tie)/i', $keywordStr)) {
                $entity = 'starships';
            }
            // Vehicle hints
            elseif (preg_match('/(crawler|speeder|walker|tank|transport|skiff|sail|cycle|bike|landspeeder)/i', $keywordStr)) {
                $entity = 'vehicles';
            }
            // Species hints (least specific - many overlap with people)
            elseif (preg_match('/(wookiee|ewok|droid|dug|sullustan|weequay|tusken|jawas|gungan|trandoshan|rodian|zabrak|yoda|jedi|sith)/i', $keywordStr)) {
                $entity = 'species';
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
                'climate' => 'planets',
                'terrain' => 'planets',
                'gravity' => 'planets',
                'skin_color' => 'people',
                'hair_color' => 'people',
                'eye_color' => 'people',
                'gender' => 'people',
                'birth_year' => 'people',
                'height' => 'people',
                'mass' => 'people',
                'classification' => 'species',
                'language' => 'species',
                'designation' => 'species',
                'average_height' => 'species',
                'average_lifespan' => 'species',
                'vehicle_class' => 'vehicles',
                'starship_class' => 'starships',
                'MGLT' => 'starships',
                'hyperdrive_rating' => 'starships',
                'max_atmosphering_speed' => 'vehicles',
                'model' => 'vehicles',
                'manufacturer' => 'vehicles',
                'cost_in_credits' => 'starships',
                'cargo_capacity' => 'starships',
                'consumables' => 'vehicles',
                'skin_colors' => 'species',
                'hair_colors' => 'species',
                'eye_colors' => 'species',
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

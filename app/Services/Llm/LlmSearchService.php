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

        return $this->extractJson($content);
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

    private function fallback(string $text): array
    {
        Log::warning("Fallback parser used", ['text' => $text]);

        $entities = ['planets', 'films', 'people', 'species', 'starships', 'vehicles'];
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

        $words = preg_split('/\s+/', trim($text));

        // Check if first word is an entity type (singular or plural)
        if (count($words) > 0) {
            $firstWord = strtolower($words[0]);
            if (isset($entityMap[$firstWord])) {
                $entity = $entityMap[$firstWord];
                $keywords = array_slice($words, 1);
                return [
                    "entity"    => $entity,
                    "keywords"  => $keywords ?: [$text],
                    "filters"   => [],
                    "relations" => [],
                    "match"     => []
                ];
            }
        }

        return [
            "entity"    => "mixed",
            "keywords"  => $text ? [$text] : [],
            "filters"   => [],
            "relations" => [],
            "match"     => []
        ];
    }

    private function systemPrompt(): string
    {
        return file_get_contents(
            resource_path('prompts/starwars_search_prompt.txt')
        );
    }
}

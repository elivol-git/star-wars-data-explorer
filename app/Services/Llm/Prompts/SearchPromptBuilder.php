<?php

namespace App\Services\Llm\Prompts;

class SearchPromptBuilder
{
    public static function build(string $query): array
    {
        return [
            [
                "role" => "system",
                "content" =>
                    "You are a Star Wars database search assistant.
Return ONLY valid JSON.
No markdown.
No explanation.
No text outside JSON."
            ],
            [
                "role" => "user",
                "content" =>
                    "User query: \"$query\"

Return JSON in format:
{
  \"entity\": \"planets|films|starships|people|vehicles|species\",
  \"keywords\": [],
  \"filters\": {}
}

Examples:

Query: \"desert planet with two suns\"
Return:
{
  \"entity\": \"planets\",
  \"keywords\": [\"desert\", \"two suns\"],
  \"filters\": {\"climate\": \"arid\"}
}

Query: \"movie about anakin\"
Return:
{
  \"entity\": \"films\",
  \"keywords\": [\"anakin\"],
  \"filters\": {}
}"
            ]
        ];
    }
}

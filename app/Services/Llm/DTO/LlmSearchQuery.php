<?php

namespace App\Services\Llm\DTO;

class LlmSearchQuery
{
    public function __construct(
        public string $entity,
        public array $keywords = [],
        public array $filters = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            entity: $data["entity"] ?? "",
            keywords: $data["keywords"] ?? [],
            filters: $data["filters"] ?? []
        );
    }
}

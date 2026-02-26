<?php

namespace App\Services\Llm\Clients;

interface LlmClientInterface
{
    public function chat(array $messages, array $options = []): array;
}

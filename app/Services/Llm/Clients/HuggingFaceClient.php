<?php

namespace App\Services\Llm\Clients;

use Illuminate\Support\Facades\Http;

class HuggingFaceClient implements LlmClientInterface
{
    private string $baseUrl;
    private string $token;
    private string $model;

    public function __construct()
    {
        $this->baseUrl = config("services.huggingface.base_url");
        $this->token = config("services.huggingface.token");
        $this->model = config("services.huggingface.model");
    }

    public function chat(array $messages, array $options = []): array
    {
        $payload = array_merge([
            "model" => $this->model,
            "messages" => $messages,
            "temperature" => 0.2,
        ], $options);

        $res = Http::withToken($this->token)
            ->post($this->baseUrl, $payload);

        if (!$res->successful()) {
            throw new \Exception("HuggingFace API Error: " . $res->body());
        }

        return $res->json();
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiClient
{
    public function __construct() {}

    public function generate(string $prompt): string
    {
        $key = config('services.gemini.key');
        $model = config('services.gemini.model');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $res = Http::timeout(60)->post($url, [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 6000,
            ],
        ]);

        if (!$res->ok()) {
            throw new \RuntimeException("Gemini error: ".$res->body());
        }

        return (string) data_get($res->json(), 'candidates.0.content.parts.0.text', '');
    }

    public function embed(string $text): array
    {
        $key = config('services.gemini.key');
        $model = config('services.gemini.embed_model');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$key}";

        $res = Http::timeout(30)->post($url, [
            'content' => [
                'parts' => [['text' => $text]],
            ],
        ]);

        if (!$res->ok()) {
            throw new \RuntimeException("Gemini embed error: ".$res->body());
        }

        $vec = data_get($res->json(), 'embedding.values', []);
        if (!is_array($vec) || count($vec) < 10) {
            throw new \RuntimeException("Invalid embedding response");
        }

        return $vec;
    }
}
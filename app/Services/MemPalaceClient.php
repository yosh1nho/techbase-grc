<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MemPalaceClient
{
    private string $baseUrl = 'http://localhost:8001';

    /**
     * Guarda um evento no histórico (Ex: Um risco foi fechado, um alerta foi resolvido)
     */
    public function remember(string $sourceId, string $content): bool
    {
        $response = Http::post("{$this->baseUrl}/mine", [
            'source_id' => $sourceId,
            'content' => $content
        ]);

        return $response->successful();
    }

    /**
     * Procura contexto histórico antes de enviar para o Gemini
     */
    public function recall(string $query): string
    {
        $response = Http::post("{$this->baseUrl}/search", [
            'query' => $query
        ]);

        if ($response->successful()) {
            return $response->json('context') ?? '';
        }

        return "Sem contexto histórico disponível.";
    }
}
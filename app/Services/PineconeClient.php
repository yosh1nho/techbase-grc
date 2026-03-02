<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PineconeClient
{
    public function query(array $vector, int $topK, string $namespace, ?array $filter = null): array
    {
        $host = rtrim(config('services.pinecone.host'), '/');
        $key  = config('services.pinecone.key');

        $payload = [
            'vector' => $vector,
            'topK' => $topK,
            'includeMetadata' => true,
            'namespace' => $namespace ?: config('services.pinecone.ns_default'),
        ];

        if ($filter) $payload['filter'] = $filter;

        $res = Http::timeout(30)
            ->withHeaders([
                'Api-Key' => $key,
                'Content-Type' => 'application/json',
            ])
            ->post($host . '/query', $payload);

        if (!$res->ok()) {
            throw new \RuntimeException("Pinecone error: " . $res->body());
        }

        return (array) data_get($res->json(), 'matches', []);
    }

    /**
     * MODO "IGUAL AO SCRIPT PYTHON":
     * Pinecone faz a pesquisa semântica a partir de texto (search_records).
     */
    public function searchRecordsText(string $text, int $topK, string $namespace, ?array $filter = null): array
    {
        $host = rtrim(config('services.pinecone.host'), '/');
        $key  = config('services.pinecone.key');

        // Endpoint de records search (compatível com search_records do SDK)
        $url = $host . "/records/namespaces/{$namespace}/search";

        $query = [
            'inputs' => ['text' => $text],
            'top_k' => $topK,
        ];
        if ($filter) $query['filter'] = $filter;

        $payload = [
            'query' => $query,
            'fields' => [
                'text',
                'doc_id','doc_name','version','chunk_index','source_file',
                'section_type',
                'control_code','control_family',
                'article_num','article_code','chapter',
            ],
        ];

        $res = Http::timeout(30)
            ->withHeaders([
                'Api-Key' => $key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($url, $payload);

        if (!$res->ok()) {
            throw new \RuntimeException("Pinecone search_records error: " . $res->body());
        }

        return (array) data_get($res->json(), 'result.hits', []);
    }
}
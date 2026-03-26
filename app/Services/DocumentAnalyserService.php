<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class DocumentAnalyserService
{
    private const MIN_SCORE      = 0.55;
    private const MAX_CONTROLS   = 8;
    private const CHUNK_WORDS    = 400;
    private const MAX_CHUNKS     = 6;
    private const HITS_PER_CHUNK = 5;

    public function __construct(
        private PineconeClient $pinecone,
    ) {}

    // =========================================================================
    public function analyse(string $filePath, string $tenantId, string $mimeType = 'application/pdf'): array
    {
        $fullPath = Storage::disk('attachments')->path($filePath);
        $text     = $this->extractText($fullPath, $mimeType);

        if (!$text || mb_strlen(trim($text)) < 80) {
            return [
                'suggestions' => [],
                'text_length' => mb_strlen($text ?? ''),
                'chunks_sent' => 0,
                'total_hits'  => 0,
                'error'       => 'Texto insuficiente para análise (mínimo 80 caracteres).',
            ];
        }

        $chunks    = array_slice($this->chunkText($text, self::CHUNK_WORDS), 0, self::MAX_CHUNKS);
        $aggregated = [];
        $totalHits  = 0;

        foreach ($chunks as $chunk) {
            try {
                $hits = $this->pinecone->searchRecordsText(
                    text:      $chunk,
                    topK:      self::HITS_PER_CHUNK,
                    namespace: $tenantId,
                );
            } catch (\Exception $e) {
                \Log::warning('DocumentAnalyser chunk falhou', ['error' => $e->getMessage()]);
                continue;
            }

            foreach ($hits as $h) {
                $fields      = $h['fields'] ?? [];
                $get         = fn($k) => $h[$k] ?? $fields[$k] ?? null;
                $controlCode = (string) ($get('control_code') ?? '');
                $score       = (float)  ($h['_score'] ?? 0);

                if (!$controlCode || $score < self::MIN_SCORE) continue;

                $totalHits++;

                if (!isset($aggregated[$controlCode])) {
                    $aggregated[$controlCode] = [
                        'control_code'   => $controlCode,
                        'control_family' => (string) ($get('control_family') ?? ''),
                        'framework'      => (string) ($get('doc_name') ?? $get('doc_title') ?? ''),
                        'scores'         => [],
                        'snippets'       => [],
                    ];
                }

                $aggregated[$controlCode]['scores'][] = $score;
                $snippet = (string) ($get('text') ?? '');
                if ($snippet) {
                    $aggregated[$controlCode]['snippets'][] = mb_substr($snippet, 0, 200);
                }
            }
        }

        $suggestions = [];
        foreach ($aggregated as $ctrl) {
            $scores     = $ctrl['scores'];
            $avg        = array_sum($scores) / count($scores);
            $max        = max($scores);
            $finalScore = round($avg * 0.4 + $max * 0.6, 3);
            $topSnippet = $ctrl['snippets'][0] ?? '';

            $suggestions[] = [
                'control_code'   => $ctrl['control_code'],
                'control_family' => $ctrl['control_family'],
                'framework'      => $this->shortFramework($ctrl['framework']),
                'score'          => $finalScore,
                'coverage'       => $this->scoreToCoverage($finalScore),
                'hit_count'      => count($scores),
                'top_snippet'    => $topSnippet,
                'justification'  => mb_substr(trim($topSnippet), 0, 120) . (mb_strlen($topSnippet) > 120 ? '…' : ''),
            ];
        }

        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'suggestions' => array_slice($suggestions, 0, self::MAX_CONTROLS),
            'text_length' => mb_strlen($text),
            'chunks_sent' => count($chunks),
            'total_hits'  => $totalHits,
        ];
    }

    // =========================================================================
    // Extracção de texto
    // =========================================================================
    private function extractText(string $path, string $mime): string
    {
        if (str_contains($mime, 'text/plain') || str_ends_with($path, '.txt') || str_ends_with($path, '.md')) {
            return file_get_contents($path) ?: '';
        }
        if (str_contains($mime, 'wordprocessingml') || str_ends_with($path, '.docx')) {
            return $this->extractDocx($path);
        }
        if (str_contains($mime, 'pdf') || str_ends_with($path, '.pdf')) {
            return $this->extractPdf($path);
        }
        return '';
    }

    private function extractDocx(string $path): string
    {
        if (!class_exists('ZipArchive')) return '';
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return '';
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!$xml) return '';
        $text = strip_tags(str_replace('</w:p>', "\n", $xml));
        return preg_replace('/\s{3,}/', "\n\n", $text) ?: '';
    }

    private function extractPdf(string $path): string
    {
        $content = @file_get_contents($path);
        if (!$content) return '';
        $text = '';

        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $m)) {
            foreach ($m[1] as $block) {
                if (preg_match_all('/\(([^)\\\\]*(?:\\\\.[^)\\\\]*)*)\)\s*T[jJ*]/', $block, $sm)) {
                    foreach ($sm[1] as $s) {
                        $d = stripcslashes($s);
                        if (!mb_check_encoding($d, 'UTF-8')) $d = mb_convert_encoding($d, 'UTF-8', 'ISO-8859-1');
                        $text .= $d . ' ';
                    }
                }
                if (preg_match_all('/<([0-9a-fA-F]+)>\s*T[jJ*]/', $block, $hm)) {
                    foreach ($hm[1] as $hex) {
                        $bin = hex2bin($hex);
                        if (!mb_check_encoding($bin, 'UTF-8')) $bin = mb_convert_encoding($bin, 'UTF-8', 'ISO-8859-1');
                        $text .= $bin . ' ';
                    }
                }
            }
        }

        // Fallback: texto imprimível
        if (mb_strlen(trim($text)) < 80) {
            preg_match_all('/[^\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]{4,}/', $content, $p);
            $text = implode(' ', array_filter($p[0], fn($s) => preg_match('/[a-zA-ZÀ-ú]{3,}/', $s)));
        }

        return $this->cleanText($text);
    }

    // =========================================================================
    // Chunking
    // =========================================================================
    private function chunkText(string $text, int $wordsPerChunk): array
    {
        $text  = $this->cleanText($text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) return [];
        $chunks = [];
        $total  = count($words);
        for ($i = 0; $i < $total; $i += $wordsPerChunk) {
            $chunk = implode(' ', array_slice($words, $i, $wordsPerChunk));
            if (mb_strlen($chunk) > 40) $chunks[] = $chunk;
        }
        return $chunks;
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', ' ', $text);
        return trim(preg_replace('/\s{3,}/', "\n\n", $text));
    }

    // =========================================================================
    // Helpers
    // =========================================================================
    private function scoreToCoverage(float $score): string
    {
        if ($score >= 0.78) return 'high';
        if ($score >= 0.65) return 'medium';
        return 'low';
    }

    private function shortFramework(string $name): string
    {
        $n = mb_strtolower($name);
        if (str_contains($n, 'nis2') || str_contains($n, 'nis 2')) return 'NIS2';
        if (str_contains($n, 'qnrcs') || str_contains($n, 'cncs'))  return 'QNRCS';
        return mb_substr($name, 0, 20) ?: '';
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IngestDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public int    $docId;
    public string $filePath;
    public string $docName;
    public string $profile;

    public function __construct(int $docId, string $filePath, string $docName, string $profile = 'auto')
    {
        $this->docId    = $docId;
        $this->filePath = $filePath;
        $this->docName  = $docName;
        $this->profile  = $profile;
    }

    public function handle(): void
    {
        $absolutePath = Storage::disk('attachments')->path($this->filePath);

        if (!file_exists($absolutePath)) {
            $this->markError("Ficheiro nao encontrado: {$absolutePath}");
            return;
        }

        $ext       = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $supported = ['pdf', 'txt', 'md', 'docx'];

        if (!in_array($ext, $supported)) {
            Log::info("IngestDocumentJob: formato .{$ext} nao indexavel, doc {$this->docId}.");
            DB::table('document')->where('id_doc', $this->docId)->update(['indexed_at' => null]);
            return;
        }

        $scriptPath = base_path('rag/ingest_pinecone_only.py');

        if (!file_exists($scriptPath)) {
            $this->markError("Script Python nao encontrado em: {$scriptPath}");
            return;
        }

        $pythonBin = env('PYTHON_BIN', '');
        if (!$pythonBin) {
            $pythonBin = $this->detectPython();
        }
        if (!$pythonBin) {
            $this->markError("Python nao encontrado. Define PYTHON_BIN no .env (ex: PYTHON_BIN=python)");
            return;
        }

        $namespace = (string) $this->docId;

        $args = [
            $pythonBin,
            $scriptPath,
            '--file',     $absolutePath,
            '--tenant',   $namespace,
            '--doc-id',   $namespace,
            '--doc-name', $this->docName,
            '--profile',  $this->profile,
        ];

        $envVars = array_merge($_ENV, [
            'PINECONE_API_KEY' => env('PINECONE_API_KEY', ''),
            'PINECONE_INDEX'   => env('PINECONE_INDEX', ''),
        ]);

        Log::info("IngestDocumentJob: executando", [
            'doc_id' => $this->docId,
            'python' => $pythonBin,
            'file'   => $absolutePath,
        ]);

        [$stdout, $stderr, $exitCode] = $this->runProcess($args, $envVars);

        if ($stderr) {
            Log::warning("IngestDocumentJob stderr", ['doc_id' => $this->docId, 'err' => substr($stderr, 0, 800)]);
        }

        // Parsear JSON da ultima linha do stdout
        $result = null;
        foreach (array_reverse(explode("\n", trim($stdout))) as $line) {
            $line = trim($line);
            if (str_starts_with($line, '{')) {
                $result = json_decode($line, true);
                break;
            }
        }

        if (!$result) {
            $this->markError("Output inesperado. stdout: " . substr($stdout, 0, 300) . " stderr: " . substr($stderr, 0, 300));
            return;
        }

        if (!($result['ok'] ?? false)) {
            $this->markError("Ingest falhou: " . ($result['error'] ?? 'erro desconhecido'));
            return;
        }

        $this->saveChunks($result);

        Log::info("IngestDocumentJob: sucesso", [
            'doc_id' => $this->docId,
            'chunks' => $result['chunks'] ?? 0,
        ]);
    }

    private function detectPython(): string
    {
        foreach (['python', 'python3'] as $candidate) {
            $test = @shell_exec("{$candidate} --version 2>&1");
            if ($test && stripos($test, 'python') !== false) {
                return $candidate;
            }
        }
        return '';
    }

    private function runProcess(array $args, array $envVars): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cmd = implode(' ', array_map('escapeshellarg', $args));

        $process = proc_open($cmd, $descriptors, $pipes, null, $envVars);

        if (!is_resource($process)) {
            return ['', 'proc_open falhou', 1];
        }

        fclose($pipes[0]);
        $stdout   = stream_get_contents($pipes[1]);
        $stderr   = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [$stdout ?: '', $stderr ?: '', $exitCode];
    }

    private function saveChunks(array $result): void
    {
        $totalChunks = (int)($result['chunks']   ?? 0);
        $namespace   = $result['namespace'] ?? (string) $this->docId;

        DB::table('document_chunk')->where('id_doc', $this->docId)->delete();

        if ($totalChunks > 0) {
            $rows = [];
            for ($i = 0; $i < $totalChunks; $i++) {
                $rows[] = [
                    'id_doc'      => $this->docId,
                    'chunk_index' => $i,
                    'pinecone_id' => "{$namespace}:{$i}",
                ];
            }
            foreach (array_chunk($rows, 200) as $batch) {
                DB::table('document_chunk')->insert($batch);
            }
        }

        DB::table('document')->where('id_doc', $this->docId)->update([
            'indexed_at'   => now(),
            'chunk_count'  => $totalChunks,
            'ingest_error' => null,
        ]);
    }

    private function markError(string $reason): void
    {
        Log::error("IngestDocumentJob error", ['doc_id' => $this->docId, 'reason' => $reason]);
        DB::table('document')->where('id_doc', $this->docId)->update([
            'ingest_error' => $reason,
            'indexed_at'   => null,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("IngestDocumentJob::failed", ['doc_id' => $this->docId, 'error' => $e->getMessage()]);
        DB::table('document')->where('id_doc', $this->docId)->update(['ingest_error' => $e->getMessage()]);
    }
}

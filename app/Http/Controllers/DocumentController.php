<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\IngestDocumentJob;

class DocumentController extends Controller
{
    private const INGESTABLE_EXTS = ["pdf", "txt", "md", "docx"];

    // =========================================================================
    // GET /api/documents
    // ?status=pending|approved|rejected|all  (default: all)
    // ?type=framework|evidence|policy|...    (opcional)
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = DB::table("document as d")
            ->leftJoin("User as uploader", "uploader.id_user", "=", "d.uploaded_by")
            ->leftJoin("User as approver", "approver.id_user", "=", "d.approved_by")
            ->leftJoin("attachment as a",  "a.id_attachment",  "=", "d.id_attachment")
            ->leftJoin("treatmentcomment as tc", "tc.id_comment", "=", "d.promoted_from_comment")
            ->leftJoin("treatmenttask as tt",    "tt.id_task",    "=", "tc.id_task")
            ->leftJoin("risktreatmentplan as rtp","rtp.id_plan",  "=", "tt.id_plan")
            ->leftJoin("risk as r",              "r.id_risk",     "=", "rtp.id_risk")
            ->select([
                "d.id_doc", "d.title", "d.type", "d.status", "d.version",
                "d.file_path", "d.created_at", "d.approved_at",
                "d.id_attachment", "d.promoted_from_comment",
                "d.indexed_at", "d.chunk_count", "d.ingest_error",
                "a.file_name", "a.original_name", "a.mime_type", "a.file_size",
                "a.file_path as attach_path",
                "uploader.name  as uploader_name",
                "uploader.email as uploader_email",
                "approver.name  as approver_name",
                "r.id_risk", "r.title as risk_title",
                "tt.id_task", "tt.title as task_title",
            ])
            ->whereNull("d.deleted_at");

        if ($request->filled("status") && $request->status !== "all") {
            $query->where("d.status", $request->status);
        }
        if ($request->filled("type")) {
            $query->where("d.type", $request->type);
        }

        $docs = $query->orderByDesc("d.created_at")->get();
        return response()->json($docs->map(fn($d) => $this->formatDoc($d)));
    }

    // =========================================================================
    // POST /api/documents/upload
    // =========================================================================
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            "file"    => ["required", "file", "max:51200"],
            "title"   => ["required", "string", "max:255"],
            "type"    => ["required", "string", "in:evidence,policy,procedure,report,framework"],
            "version" => ["nullable", "string", "max:50"],
            "date"    => ["nullable", "date"],
            "source"  => ["nullable", "string", "max:100"],
            "notes"   => ["nullable", "string"],
        ]);

        $file        = $request->file("file");
        $userId      = session("tb_user.id") ?? null;
        $isFramework = $request->input("type") === "framework";
        $ext         = strtolower($file->getClientOriginalExtension());

        // Guardar ficheiro
        $storedName = Str::uuid() . "." . $ext;
        $yearMonth  = now()->format("Y/m");
        $path       = $file->storeAs($yearMonth, $storedName, "attachments");

        // Attachment
        $attachmentId = DB::table("attachment")->insertGetId([
            "file_name"  => $file->getClientOriginalName(),
            "file_path"  => $path,
            "mime_type"  => $file->getClientMimeType(),
            "created_at" => now(),
        ], "id_attachment");

        $status = $isFramework ? "approved" : "pending";

        $docId = DB::table("document")->insertGetId([
            "id_attachment" => $attachmentId,
            "title"         => $request->input("title"),
            "type"          => $request->input("type"),
            "status"        => $status,
            "version"       => $request->input("version", "1.0"),
            "file_path"     => $path,
            "uploaded_by"   => $userId,
            "approved_by"   => $isFramework ? $userId : null,
            "approved_at"   => $isFramework ? now() : null,
            "created_at"    => now(),
        ], "id_doc");

        $ingestResult = "skipped";

        if (in_array($ext, self::INGESTABLE_EXTS) && $isFramework) {
            IngestDocumentJob::dispatch(
                $docId,
                $path,
                $request->input("title"),
                "auto"
            );
            $ingestResult = "queued";
        }

        return response()->json([
            "success" => true,
            "doc_id"  => $docId,
            "status"  => $status,
            "ingest"  => $ingestResult,
            "message" => $isFramework
                ? "Framework guardado e a indexar no Pinecone."
                : "Documento enviado. Aguarda aprovação.",
        ], 201);
    }

    // =========================================================================
    // POST /api/documents/{id}/approve
    // =========================================================================
    public function approve(int $id): JsonResponse
    {
        $doc = DB::table("document as d")
            ->leftJoin("attachment as a", "a.id_attachment", "=", "d.id_attachment")
            ->select(["d.*", "a.file_path as attach_path", "a.original_name", "a.file_name"])
            ->where("d.id_doc", $id)
            ->first();

        if (!$doc) {
            return response()->json(["success" => false, "message" => "Documento não encontrado."], 404);
        }
        if ($doc->status === "approved") {
            return response()->json(["success" => false, "message" => "Documento já aprovado."], 409);
        }

        $userId = session("tb_user.id") ?? null;
        DB::table("document")->where("id_doc", $id)->update([
            "status"      => "approved",
            "approved_by" => $userId,
            "approved_at" => now(),
        ]);

        $filePath = $doc->attach_path ?? $doc->file_path ?? null;
        $ext      = $filePath ? strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) : null;

        if ($filePath && in_array($ext, self::INGESTABLE_EXTS)) {
            $docName = $doc->original_name ?? $doc->file_name ?? $doc->title ?? "documento";
            IngestDocumentJob::dispatch($id, $filePath, $docName, "auto");

            return response()->json([
                "success" => true,
                "message" => "Aprovado. A indexar no Pinecone...",
                "ingest"  => "queued",
            ]);
        }

        return response()->json([
            "success" => true,
            "message" => "Documento aprovado.",
            "ingest"  => "skipped",
        ]);
    }

    // =========================================================================
    // POST /api/documents/{id}/reject
    // =========================================================================
    public function reject(int $id): JsonResponse
    {
        $doc = DB::table("document")->where("id_doc", $id)->first();
        if (!$doc) {
            return response()->json(["success" => false, "message" => "Documento não encontrado."], 404);
        }
        DB::table("document")->where("id_doc", $id)->update(["status" => "rejected"]);
        return response()->json(["success" => true]);
    }

    // =========================================================================
    // GET /api/documents/{id}/download
    // =========================================================================
    public function download(int $id)
    {
        $doc = DB::table("document as d")
            ->leftJoin("attachment as a", "a.id_attachment", "=", "d.id_attachment")
            ->select(["d.id_doc", "d.title", "d.file_path", "a.file_path as attach_path", "a.original_name", "a.file_name"])
            ->where("d.id_doc", $id)
            ->first();

        if (!$doc) abort(404, "Documento não encontrado.");

        $path        = $doc->attach_path ?? $doc->file_path;
        $displayName = $doc->original_name ?? $doc->file_name ?? $doc->title ?? "documento";

        if (!$path || !Storage::disk("attachments")->exists($path)) {
            abort(404, "Ficheiro não encontrado no servidor.");
        }

        return Storage::disk("attachments")->download($path, $displayName);
    }

    // =========================================================================
    // Helper
    // =========================================================================
    private function formatDoc($d): array
    {
        $fileName = $d->original_name ?? $d->file_name ?? $d->title ?? "—";
        return [
            "id"           => $d->id_doc,
            "title"        => $d->title ?? $fileName,
            "type"         => $d->type  ?? "evidence",
            "status"       => $d->status ?? "pending",
            "version"      => $d->version ?? "1.0",
            "file_name"    => $fileName,
            "mime_type"    => $d->mime_type ?? null,
            "file_size"    => $d->file_size ?? null,
            "created_at"   => $d->created_at,
            "approved_at"  => $d->approved_at,
            "indexed_at"   => $d->indexed_at ?? null,
            "chunk_count"  => $d->chunk_count ?? null,
            "ingest_error" => $d->ingest_error ?? null,
            "uploader"     => $d->uploader_name ?? $d->uploader_email ?? "Utilizador",
            "approver"     => $d->approver_name ?? null,
            "origin"       => $d->risk_title
                ? "Risco: {$d->risk_title}" . ($d->task_title ? " > {$d->task_title}" : "")
                : null,
            "has_file"     => !empty($d->attach_path ?? $d->file_path),
        ];
    }
}

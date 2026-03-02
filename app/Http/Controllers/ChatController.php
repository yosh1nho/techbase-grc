<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RagChatService;

class ChatController extends Controller
{
    public function ask(Request $request, RagChatService $rag)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:12000'],
            'scope' => ['nullable', 'array'],
        ]);

        // ✅ pega tenant do mock auth (sessão)
        $tbUser = session('tb_user', []);
        $tenantId = (string)($tbUser['tenant'] ?? $tbUser['tenant_id'] ?? '102'); // fallback 102

        $result = $rag->answer(
            question: $data['question'],
            tenantId: $tenantId,
            scope: $data['scope'] ?? []
        );

        return response()->json($result);
    }
}
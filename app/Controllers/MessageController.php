<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Message;
use Slenix\Http\Request;
use Slenix\Http\Response;

class MessageController
{
    /**
     * Retorna o histórico de mensagens entre o utilizador autenticado e um amigo.
     *
     * Usa duas queries simples (uma por direcção de envio) para contornar a
     * limitação do QueryBuilder do Slenix com orWhere() composto. O merge e a
     * ordenação são feitos em PHP puro, sem depender de métodos da Collection.
     *
     * GET /messages/{id}
     *
     * @param Request              $req
     * @param Response             $res
     * @param array<string, mixed> $args Espera `id` = friendId na rota.
     * @return Response JSON array de mensagens.
     */
    public function index(Request $req, Response $res, array $args)
    {
        $userId   = (int) auth()->id();
        $friendId = (int) $args['id'];

        // ── Query A: mensagens que EU enviei AO amigo ─────────────────────
        $sent = Message::newQuery()
            ->where('sender_id', $userId)
            ->where('receiver_id', $friendId)
            ->orderBy('created_at')
            ->getArray();   // retorna array associativo — sem Collection

        // ── Query B: mensagens que O AMIGO me enviou ──────────────────────
        $received = Message::newQuery()
            ->where('sender_id', $friendId)
            ->where('receiver_id', $userId)
            ->orderBy('created_at')
            ->getArray();   // retorna array associativo — sem Collection

        error_log("[MessageController] sent=" . count($sent) . " received=" . count($received));

        // ── Merge + ordenação por created_at em PHP ───────────────────────
        $all = array_merge($sent, $received);
        usort($all, fn($a, $b) =>
            strtotime($a['created_at'] ?? '0') <=> strtotime($b['created_at'] ?? '0')
        );

        foreach ($received as $row) {
            if (!(bool) $row['is_read']) {
                $msg = Message::find((int) $row['id']);
                if ($msg) {
                    $msg->is_read = true;
                    $msg->save();
                }
            }
        }

        $payload = array_values(array_map(fn($row) => [
            'id'          => (int) $row['id'],
            'sender_id'   => (int) $row['sender_id'],
            'receiver_id' => (int) $row['receiver_id'],
            'body'        => $row['body'],                  // NULL para GIF
            'msg_type'    => $row['type'],                  // BD: 'type' → JS: 'msg_type'
            'sticker_url' => $row['sticker_url'],           // URL para GIF, NULL para os demais
            'is_read'     => (bool) $row['is_read'],
            'time'        => isset($row['created_at'])
                ? date('H:i', strtotime($row['created_at']))
                : date('H:i'),
        ], $all));

        error_log("[MessageController] payload count=" . count($payload));

        return $res->json($payload);
    }
}
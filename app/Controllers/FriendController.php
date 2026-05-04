<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Friend;
use Slenix\Http\Request;
use Slenix\Http\Response;

/**
 * FriendController
 *
 * Gere o ciclo de vida das amizades: envio de pedido, aceitação,
 * rejeição e remoção. Todas as acções respondem via JSON (para
 * pedidos AJAX/WebSocket) ou redireccionam em caso de erro/acesso inválido.
 *
 * @package App\Controllers
 */
class FriendController
{
    /**
     * Envia um pedido de amizade ao utilizador especificado.
     *
     * Regras de validação:
     *  - Não pode enviar pedido a si próprio.
     *  - Não pode duplicar um pedido já existente (em qualquer direcção).
     *
     * POST /friends/{id}/request
     *
     * @param Request              $req
     * @param Response             $res
     * @param array<string, mixed> $args Espera `id` = receiverId.
     * @return Response JSON com `type` ou redirect em caso de erro.
     */
    public function store(Request $req, Response $res, array $args)
    {
        $receiverId = (int) $args['id'];
        $senderId   = (int) auth()->id();

        // Não pode enviar pedido a si próprio
        if ($receiverId === $senderId) {
            return $res->status(422)->json([
                'type'    => 'error',
                'message' => 'Não podes enviar um pedido a ti próprio.',
            ]);
        }

        // Verifica se já existe relação em qualquer direcção
        $existsForward = Friend::newQuery()
            ->where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->exists();

        $existsInverse = Friend::newQuery()
            ->where('sender_id', $receiverId)
            ->where('receiver_id', $senderId)
            ->exists();

        if ($existsForward || $existsInverse) {
            return $res->status(409)->json([
                'type'    => 'error',
                'message' => 'Já existe um pedido ou amizade com este utilizador.',
            ]);
        }

        Friend::create([
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'status'      => 'pending',
        ]);

        // Resposta JSON para o JS poder notificar via WebSocket
        return $res->status(201)->json([
            'type'        => 'friend_request_sent',
            'receiver_id' => $receiverId,
        ]);

    }

    /**
     * Aceita um pedido de amizade pendente.
     *
     * Apenas o destinatário original do pedido pode aceitar.
     * Responde com JSON para o JS notificar o remetente via WebSocket.
     *
     * POST /friends/{id}/accept
     *
     * @param Request              $req
     * @param Response             $res
     * @param array<string, mixed> $args Espera `id` = friendship id.
     * @return Response JSON com `type` e `friend_id`.
     */
    public function accept(Request $req, Response $res, array $args)
    {
        $userId = (int) auth()->id();

        $friendship = Friend::newQuery()
            ->where('id', (int) $args['id'])
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return $res->status(404)->json([
                'type'    => 'error',
                'message' => 'Pedido de amizade não encontrado.',
            ]);
        }

        $friendship->status = 'accepted';
        $friendship->save();

        // friend_id = quem enviou o pedido (o outro lado)
        return $res->json([
            'type'      => 'friend_accepted',
            'friend_id' => (int) $friendship->sender_id,
        ]);

    }

    /**
     * Rejeita um pedido de amizade pendente.
     *
     * Apenas o destinatário original pode rejeitar.
     * Marca o registo como 'rejected' em vez de o apagar,
     * para evitar que o mesmo pedido seja enviado repetidamente.
     *
     * POST /friends/{id}/reject
     *
     * @param Request              $req
     * @param Response             $res
     * @param array<string, mixed> $args Espera `id` = friendship id.
     * @return Response JSON com confirmação.
     */
    public function reject(Request $req, Response $res, array $args)
    {
        $userId = (int) auth()->id();

        $friendship = Friend::newQuery()
            ->where('id', (int) $args['id'])
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return $res->status(404)->json([
                'type'    => 'error',
                'message' => 'Pedido de amizade não encontrado.',
            ]);
        }

        $friendship->status = 'rejected';
        $friendship->save();

        return $res->json([
            'type'         => 'friend_rejected',
            'friendship_id' => (int) $friendship->id,
        ]);
    }

    /**
     * Remove uma amizade existente.
     *
     * Qualquer um dos dois lados pode remover a amizade.
     * Usa duas queries separadas (uma por direcção) para contornar
     * a limitação do QueryBuilder do Slenix com orWhere() composto.
     *
     * DELETE /friends/{id}
     *
     * @param Request              $req
     * @param Response             $res
     * @param array<string, mixed> $args Espera `id` = friendship id.
     * @return Response JSON com confirmação ou erro.
     */
    public function destroy(Request $req, Response $res, array $args)
    {
        $userId      = (int) auth()->id();
        $friendshipId = (int) $args['id'];

        // Tenta encontrar onde o utilizador é o remetente
        $friendship = Friend::newQuery()
            ->where('id', $friendshipId)
            ->where('sender_id', $userId)
            ->first();

        // Se não encontrou, tenta onde é o destinatário
        if (!$friendship) {
            $friendship = Friend::newQuery()
                ->where('id', $friendshipId)
                ->where('receiver_id', $userId)
                ->first();
        }

        if (!$friendship) {
            return $res->status(404)->json([
                'type'    => 'error',
                'message' => 'Amizade não encontrada.',
            ]);
        }

        $friendship->delete();

        return $res->json([
            'type'         => 'friend_removed',
            'friendship_id' => $friendshipId,
        ]);
    }
}
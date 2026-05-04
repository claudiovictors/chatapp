<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Message;
use App\Models\User;
use Slenix\Core\WebSocket\WebSocketHandler;
use Slenix\Core\WebSocket\Connection;
use Slenix\Supports\Libraries\Str;

/**
 * ChatController
 *
 * Controlador WebSocket responsável por toda a lógica de chat em tempo real.
 * Trata autenticação de sessão, envio/receção de mensagens (texto, sticker, GIF)
 * e notificações de amizade.
 *
 * @package App\Controllers
 */
class ChatController extends WebSocketHandler
{
    // ── Lifecycle ─────────────────────────────────────────────

    /**
     * Disparado quando uma nova conexão WebSocket é estabelecida.
     *
     * @param Connection $conn Conexão recém-aberta.
     * @return void
     */
    public function onOpen(Connection $conn): void
    {
        echo "Nova conexão: {$conn->getId()}" . PHP_EOL;
    }

    /**
     * Disparado quando uma conexão WebSocket é encerrada.
     *
     * @param Connection $conn Conexão que foi fechada.
     * @return void
     */
    public function onClose(Connection $conn): void
    {
        $userId = $conn->getAttribute('user_id');
        if ($userId) {
            echo "Conexão fechada: user_id={$userId}" . PHP_EOL;
        }
    }

    /**
     * Disparado quando ocorre um erro numa conexão.
     *
     * @param Connection $conn Conexão onde ocorreu o erro.
     * @param \Throwable $e    Exceção ou erro capturado.
     * @return void
     */
    public function onError(Connection $conn, \Throwable $e): void
    {
        echo "Erro [{$conn->getId()}]: {$e->getMessage()}" . PHP_EOL;
    }

    // ── Dispatcher ────────────────────────────────────────────

    /**
     * Ponto de entrada para todas as mensagens recebidas via WebSocket.
     *
     * Antes de qualquer ação valida se a conexão está autenticada (excepto
     * para o handshake 'auth'). Despacha para o handler adequado com base
     * no campo `type` do payload.
     *
     * Tipos suportados:
     *  - `auth`           → {@see handleAuth()}
     *  - `message`        → {@see handleMessage()}
     *  - `friend_request` → {@see handleFriendRequest()}
     *  - `friend_accepted`→ {@see handleFriendAccepted()}
     *
     * @param Connection           $conn Conexão que enviou a mensagem.
     * @param array<string, mixed> $data Payload decodificado (JSON → array).
     * @return void
     */
    public function onMessage(Connection $conn, mixed $data): void
    {
        // DEBUG — imprime o payload completo no terminal do servidor
        // Permite confirmar que msg_type e sticker_url chegam correctamente
        echo "⬇ RAW payload: " . json_encode($data) . PHP_EOL;

        $type = $data['type'] ?? '';

        if ($type === 'auth') {
            $this->handleAuth($conn, $data);
            return;
        }

        if (!$conn->getAttribute('authed', false)) {
            $conn->send(['type' => 'error', 'message' => 'Sessão não autenticada.']);
            return;
        }

        match ($type) {
            'message'          => $this->handleMessage($conn, $data),
            'friend_request'   => $this->handleFriendRequest($conn, $data),
            'friend_accepted'  => $this->handleFriendAccepted($conn, $data),
            default            => null,
        };
    }

    // ── Handlers ──────────────────────────────────────────────

    /**
     * Vincula a conexão WebSocket a um utilizador da base de dados.
     *
     * @param Connection           $conn Conexão a autenticar.
     * @param array<string, mixed> $data Payload com `user_id`.
     * @return void
     */
    private function handleAuth(Connection $conn, array $data): void
    {
        $userId = (int) ($data['user_id'] ?? 0);

        if (!$userId || !($user = User::find($userId))) {
            $conn->send(['type' => 'error', 'message' => 'Falha na autenticação: utilizador inválido.']);
            return;
        }

        $conn->setAttribute('user_id', $userId);
        $conn->setAttribute('authed', true);

        $conn->send(['type' => 'authed', 'user_id' => $userId]);
        echo "Utilizador {$userId} autenticado no socket {$conn->getId()}" . PHP_EOL;
    }

    /**
     * Processa e distribui mensagens de texto, sticker ou GIF.
     *
     * Regras de validação:
     *  - `text` e `sticker` → campo `body` obrigatório e não vazio.
     *  - `gif`              → campo `sticker_url` obrigatório e não vazio; `body` = NULL.
     *
     * Fluxo de entrega:
     *  1. Persiste no banco.
     *  2. Envia ao destinatário (se online).
     *  3. Confirma ao remetente via `message_sent`.
     *
     * @param Connection           $conn Conexão do remetente.
     * @param array<string, mixed> $data Payload com receiver_id, msg_type, body e/ou sticker_url.
     * @return void
     */
    private function handleMessage(Connection $conn, array $data): void
    {
        $senderId   = (int) $conn->getAttribute('user_id');
        $receiverId = (int) ($data['receiver_id'] ?? 0);

        if (!$receiverId || $senderId === $receiverId) {
            $conn->send(['type' => 'error', 'message' => 'Destinatário inválido.']);
            return;
        }

        // ── Normaliza msg_type ────────────────────────────────────────────────
        // Lê 'msg_type' do payload JS. Aceita apenas valores válidos.
        $msgType = (string) ($data['msg_type'] ?? 'text');
        if (!in_array($msgType, ['text', 'sticker', 'gif'], true)) {
            $msgType = 'text';
        }

        echo "📨 handleMessage | sender={$senderId} receiver={$receiverId} msg_type={$msgType}" . PHP_EOL;

        // ── Valida conteúdo por tipo ──────────────────────────────────────────
        if ($msgType === 'gif') {
            // GIF: body = NULL, sticker_url = URL obrigatória
            $body       = null;
            $stickerUrl = trim((string) ($data['sticker_url'] ?? ''));

            if (empty($stickerUrl)) {
                $conn->send(['type' => 'error', 'message' => 'URL do GIF em falta.']);
                return;
            }
        } else {
            // text / sticker: body = conteúdo obrigatório, sticker_url = NULL
            $body       = trim((string) ($data['body'] ?? ''));
            $stickerUrl = null;

            if (empty($body)) {
                $conn->send(['type' => 'error', 'message' => 'Mensagem vazia.']);
                return;
            }
        }

        // ── Persiste ──────────────────────────────────────────────────────────
        $message = Message::create([
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'body'        => $body,
            'type'        => $msgType,
            'sticker_url' => $stickerUrl,
            'is_read'     => false,
        ]);

        if (!$message) {
            $conn->send(['type' => 'error', 'message' => 'Erro ao salvar no banco.']);
            return;
        }

        echo "Salvo no banco → ID:{$message->id} type:{$msgType} sticker_url:" . ($stickerUrl ?: 'NULL') . PHP_EOL;

        // ── Payload de entrega ────────────────────────────────────────────────
        $payload = [
            'type'        => 'message',
            'id'          => $message->id,
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'body'        => $body,
            'msg_type'    => $msgType,
            'sticker_url' => $stickerUrl,
            'time'        => date('H:i'),
        ];

        // Entrega ao destinatário (pode haver múltiplas abas/dispositivos)
        $delivered = false;
        foreach ($this->getConnections() as $c) {
            if ((int) $c->getAttribute('user_id') === $receiverId) {
                $c->send($payload);
                $delivered = true;
            }
        }

        // Confirmação ao remetente — tipo distinto para o JS não renderizar
        // como mensagem recebida
        $conn->send(array_merge($payload, [
            'type'      => 'message_sent',
            'delivered' => $delivered,
        ]));
    }

    /**
     * Notifica um utilizador sobre um novo pedido de amizade em tempo real.
     *
     * @param Connection           $conn Conexão do utilizador que envia o pedido.
     * @param array<string, mixed> $data Payload com `receiver_id`.
     * @return void
     */
    private function handleFriendRequest(Connection $conn, array $data): void
    {
        $senderId   = (int) $conn->getAttribute('user_id');
        $receiverId = (int) ($data['receiver_id'] ?? 0);

        if (!$receiverId || !($sender = User::find($senderId))) {
            return;
        }

        foreach ($this->getConnections() as $c) {
            if ((int) $c->getAttribute('user_id') === $receiverId) {
                $c->send([
                    'type'      => 'friend_request',
                    'sender_id' => $senderId,
                    'name'      => "{$sender->fname} {$sender->lname}",
                    'initials'  => strtoupper(
                        substr($sender->fname, 0, 1) . substr($sender->lname, 0, 1)
                    ),
                ]);
            }
        }
    }

    /**
     * Notifica o utilizador quando um pedido de amizade foi aceite.
     *
     * @param Connection           $conn Conexão do utilizador que aceitou o pedido.
     * @param array<string, mixed> $data Payload com `friend_id`.
     * @return void
     */
    private function handleFriendAccepted(Connection $conn, array $data): void
    {
        $userId   = (int) $conn->getAttribute('user_id');
        $friendId = (int) ($data['friend_id'] ?? 0);

        if (!$friendId || !($user = User::find($userId))) {
            return;
        }

        foreach ($this->getConnections() as $c) {
            if ((int) $c->getAttribute('user_id') === $friendId) {
                $c->send([
                    'type'     => 'friend_accepted',
                    'user_id'  => $userId,
                    'name'     => "{$user->fname} {$user->lname}",
                    'initials' => strtoupper(
                        substr($user->fname, 0, 1) . substr($user->lname, 0, 1)
                    ),
                    'status'   => $user->status,
                ]);
            }
        }
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Message;
use App\Models\User;
use Slenix\Http\Message\Request;
use Slenix\Http\Message\Response;
use Slenix\Libraries\Session;

class ChatController
{

    public function showChat(Request $request, Response $response, array $params)
    {
        $friend_id = (int) $params['user_id'];
        $user_id = (int) Session::get('user_id');

        $id_user = User::find($friend_id);

        if (!$id_user) {
            redirect('/~');
            return;
        }

        $allMessages = Message::query("
        SELECT * FROM messages 
        WHERE (user_id = :user_id1 AND friend_id = :friend_id1)
           OR (user_id = :user_id2 AND friend_id = :friend_id2)
        ORDER BY created_at ASC
    ", [
            'user_id1' => $user_id,
            'friend_id1' => $friend_id,
            'user_id2' => $friend_id,
            'friend_id2' => $user_id
        ]);

        return view('pages.chat', compact(
            'id_user',
            'friend_id',
            'allMessages',
            'user_id'
        ));
    }

    public function sendMessage(Request $request, Response $response)
    {
        $user_id = (int) sanetize($request->post('user_id'));
        $friend_id = (int) sanetize($request->post('friend_id'));
        $message = sanetize($request->post('message'));

        if (!empty($message)) {
            $sendMessage = Message::create([
                'user_id' => $user_id,
                'friend_id' => $friend_id,
                'message' => $message
            ]);

            redirect("/chat/{$friend_id}");
        }

        redirect('/~');
    }

    /**
     * Renderização para a página home
     * @param \Slenix\Http\Message\Request $request
     * @param \Slenix\Http\Message\Response $response
     */
    public function index(Request $request, Response $response)
    {
        # Pega o id da sessão do usuário logado 
        $user_id = Session::get('user_id');

        # Listar usuários que não são amigos
        $nofriends = User::query("
            SELECT * FROM users u
            WHERE u.id != {$user_id}
            AND u.id NOT IN (
                SELECT friend_id FROM friends WHERE user_id = {$user_id}
                UNION
                SELECT user_id FROM friends WHERE friend_id = {$user_id}
            )
            ORDER BY created_at ASC
        ");

        # Listar amigos com pedido de amizade pendente
        $pendentes = User::query("
            SELECT u.*, f.id as friendship_id, f.status
            FROM users u
            JOIN friends f ON (
                (f.user_id = {$user_id} AND f.friend_id = u.id)
                OR (f.friend_id = {$user_id} AND f.user_id = u.id)
            )
            WHERE f.status = 'pendente'
            ORDER BY created_at ASC
        ");

        # Listar amigos com pedido de amizade aceitos
        $aceitos = $friends = User::query("
            SELECT u.*
            FROM users u
            JOIN friends f ON (
                (f.user_id = {$user_id} AND f.friend_id = u.id)
                OR (f.friend_id = {$user_id} AND f.user_id = u.id)
            )
            WHERE f.status = 'aceito' ORDER BY created_at ASC
        ");


        $userLogued = User::where('id', $user_id)->first();  # Pegando o usuário

        return view('pages.home', compact(
            'userLogued',
            'nofriends',
            'pendentes',
            'aceitos'
        ));
    }

    /**
     * Termina a sessão do usuário
     * @param \Slenix\Http\Message\Request $request
     * @param \Slenix\Http\Message\Response $response
     * @return void
     */
    public function logout(Request $request, Response $response)
    {
        # Pega o id da sessão do usuário logado            
        $user_id = Session::get('user_id');

        $destroy = User::find($user_id); # Pegando o usuário

        $destroy->status = 'Ofline'; // Atualizando o status do usuário para Ofline

        $destroy->update();  // Atualizando o status do usuário para Ofline

        Session::destroy(); // Destruindo a sessão

        redirect('/login'); // Redirecionar o usuário para a tela de login
    }
}

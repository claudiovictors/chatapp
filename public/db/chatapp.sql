Olá claude eu tenho a seguinte estrutura de banco de dados em mysql,
não consigo mostrar todas as mensagens de usuários no meu ChatController?


CREATE DATABASE chatapp DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fname VARCHAR(255) NOT NULL,
    lname VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    status VARCHAR(255) NOT NULL,
    images VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status enum('pendente', 'aceito', 'recusado') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

<?php

declare(strict_types=1);

namespace App\Models;

use Slenix\Database\Model;

class Message extends Model
{
    protected string $table = 'messages';
    protected string $primaryKey = 'id';
    protected array $fillable = ['user_id', 'friend_id', 'message'];

    protected bool $timestamps = true;

    protected array $casts = [
        'id' => 'int',
        'created_at' => 'datatime',
        'updated_at' => 'datatime'
    ];
}

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

    public function showChat(Request $request, Response $response, array $params) {
        $id = $params['user_id'];

        $id_user = User::find($id);
        $user_id = Session::get('user_id');
        $friend_id = $id;

        $messages = "SELECT * FROM messages LEFT JOIN users ON users.id = messages.user_id
                WHERE (user_id = {$user_id} AND friend_id = {$friend_id})
                OR (user_id = {$friend_id} AND friend_id = {$user_id})";

        $allMessages = Message::query($messages);

        return view('pages.chat', compact(
            'id_user',
            'id',
            'allMessages'
        ));
    }

    public function sendMessage(Request $request, Response $response){
        $user_id = (int) sanetize($request->post('user_id'));
        $friend_id = (int) sanetize($request->post('friend_id'));
        $message = sanetize($request->post('message'));

        if(!empty($message)){
            $sendMessage = Message::create([
                'user_id' => $user_id,
                'friend_id' => $friend_id,
                'message' => $message
            ]);
        }

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
        ");

        # Listar amigos com pedido de amizade aceitos
        $aceitos = $friends = User::query("
            SELECT u.*
            FROM users u
            JOIN friends f ON (
                (f.user_id = {$user_id} AND f.friend_id = u.id)
                OR (f.friend_id = {$user_id} AND f.user_id = u.id)
            )
            WHERE f.status = 'aceito'
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

@extends('layouts.main')

@section('title')
{{ env('APP_NAME') }}
@endsection

@section('content')
<div class="chat-home">
    <header class="header-chat">
    <div class="menu-left">
        <div class="image-arrow">
            <a href="{{ route('home.page') }}" class="icon">&leftarrow;</a>
            <img src="/assets/images/{{ $id_user->image }}" alt="{{ $id_user->fname }} {{  $id_user->lname }}">
        </div>
        <div class="chat-names">
            <h4 class="chat-name"> {{ $id_user->fname }} {{ $id_user->lname }}</h4>
            <p class="chat-status">{{ $id_user->status }}</p>
        </div>
    </div>

    <div class="menu-right">
        <i class="bx bx-cog"></i>
    </div>
</header>
<aside class="area-sms">

    <div class="user-content">
        <div class="messages">
            <p class="text-sms">Olá</p>
        </div>
    </div>

    <div class="friend-content">
        <img src="/assets/images/{{ $id_user->image }}" width="35" alt="{{ $id_user->fname }} {{  $id_user->lname }}">
        <div class="messages">
            <p class="text-sms">Olá, Cláudio</p>
        </div>
    </div>

</aside>
<footer class="form-send">
    <form action="/send" method="post">
        <input type="hidden" name="user_id" id="user_id" value="{{ Session::get('user_id') }}">
        <input type="hidden" name="friend_id" id="friend_id" value="{{ $id }}">
        <input type="text" name="message" id="message" placeholder=" Mensagem...">
        <button type="submit"><i class="bx bx-send"></i></button>
    </form>
</footer>
</div>

@endsection

@extends('layouts.main')

@section('title')
{{ env('APP_NAME') }}
@endsection

@section('content')
<div class="list-home">
    <div class="header-list">
    <div class="list-left">
        <a href="{{ $userLogued->id }}">
            <img src="/assets/images/{{ $userLogued->image }}" alt="{{ $userLogued->fname }}">
        </a>

        <div class="info">
            <h3 class="name">{{ $userLogued->fname }} {{ $userLogued->lname }}</h3>
            <p class="status">{{ $userLogued->status }}</p>
        </div>
    </div>

    <a href="{{ route('logout') }}" class="btn-logout">Logout</a>
</div>
<div class="body-list">
    <div class="form-serach">
        <form action="#" method="get">
            <input type="text" name="search" id="search" placeholder=" Procurar amigo...">
            <button type="submit"><i class="bx bx-search"></i></button>
        </form>
    </div>


    <!-- IMPORTANT: os inputs precisam estar antes do article e com o mesmo pai -->
    <input type="radio" name="slider" id="messages" checked>
    <input type="radio" name="slider" id="friends">

    <div class="menu-tag">
        <label for="messages">Mensagens</label>
        <label for="friends">Amigos</label>
        <div class="line"></div>
    </div>

    <article>
        
        <!-- LISTA DE MENSAGENS -->
        <div class="content content-1">
            <div class="users-list-sms">
                @if (empty($aceitos))
                 <div class="text-sms">Nenhum amigo</div>
                @else
                    @foreach ($aceitos as $aceito)
                        <a href="{{ route('chat.page') }}/{{ $aceito->id }}" class="users">
                            <div class="sms-left">
                                <img src="/assets/images/{{ $aceito->image }}" />

                                <div class="info">
                                    <h3 class="name">{{ $aceito->fname }} {{ $aceito->lname }}</h3>
                                    <p class="status">Você: Olá, Anderson!</p>
                                </div>
                            </div>

                            @if ($aceito->status == 'Online')
                                <i class="bx bxs-circle"></i>
                            @else 
                                <i class="bx bxs-circle" style="color: #989898"></i>
                            @endif
                        </a>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- LISTA DE AMIGOS -->
        <div class="content content-2">
            @if (!empty($pendentes))
                <h4>Pendentes</h4>
                <div class="users-list-sms">
                    @foreach ($pendentes as $pendente)
                        <div class="users">
                            <div class="sms-left">
                                <img src="/assets/images/{{ $pendente->image }}">

                                <div class="info">
                                    <h3 class="name">{{ $pendente->fname }} {{ $pendente->lname }}</h3>
                                    <p class="status">Novo</p>
                                </div>
                            </div>

                            <form action="{{ route('accept.friend') }}" method="post">
                                <input type="hidden" name="id" value="{{ $pendente->friendship_id }}">
                                <button type="submit">Aceitar</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <h4>Procurar amigos</h4>
            <div class="users-list-sms">
                @if (empty($nofriends))
                    <div class="text-sms">Nenhuma amigo</div>
                @else 

                    @foreach ($nofriends as $nofriend)
                        <div class="users">
                            <div class="sms-left">
                                <img src="/assets/images/{{ $nofriend->image }}">

                                <div class="info">
                                    <h3 class="name">{{ $nofriend->fname }} {{ $nofriend->lname }}</h3>
                                    <p class="status">Novo</p>
                                </div>
                            </div>

                            <form action="{{ route('add.friend') }}" method="post">
                                @csrf
                                <input type="hidden" name="friend_id" id="friend_id" value="{{ $nofriend->id }}">
                                <button>Adicionar</button>
                            </form>
                        </div>
                    @endforeach
                @endif
                
            </div>
        </div>

    </article>
</div>
</div>
@endsection
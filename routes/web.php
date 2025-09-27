<?php

declare(strict_types=1);

use Slenix\Http\Message\Router;
use App\Controllers\AuthController;
use App\Controllers\ChatController;
use App\Controllers\FriendController;

/**
 * Rotas de registrar e logar usuário com middleware GUEST
 */
Router::middleware('guest', function () {
        # Exibir a tela de registro
        Router::get('/', [AuthController::class, 'showRegister'])->name('register');
        #Exibir a tela de login
        Router::get('/login', [AuthController::class, 'showLogin'])->name('login');
        #Rota post criar novo usuário
        Router::post('/register', [AuthController::class, 'regiter'])->middleware('csrf');
        #Rota post logar com usuário cadastrado
        Router::post('/login', [AuthController::class, 'login'])->middleware('csrf');
});

Router::middleware('auth', function () {
        # Renderiza para a página inicial
        Router::get('/~', [ChatController::class, 'index'])->name('home.page');
        # Renderiza para a página de chat de papo
        Router::get('/chat/{user_id?}', [ChatController::class, 'showChat'])->name('chat.page');
        # Termina a sessão do usuário logado
        Router::get('/logout', [ChatController::class, 'logout'])->name('logout');
        # Rota para adicionar amigo
        Router::post('/add-friend', [FriendController::class, 'addFriend'])->name('add.friend');
        # Rota para aceitar amigo
        Router::post('/friend/accept', [FriendController::class, 'accept'])->name('accept.friend');
        # Rota para enviar mensagem
        Router::post('/send', [ChatController::class, 'sendMessage'])->name('add.friend');
});

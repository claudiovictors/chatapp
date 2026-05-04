<?php

declare(strict_types=1);

/**
 * --------------------------------------------------------------------------
 * Web Routes - Yov Chat System (Slenix Framework)
 * --------------------------------------------------------------------------
 * 
 * Aqui são definidas as rotas da aplicação. Cada rota é mapeada para um 
 * controlador específico, respeitando os middlewares de autenticação.
 */

use App\Controllers\AuthController;
use App\Controllers\FriendController;
use Slenix\Http\Routing\Router;
use App\Controllers\HomeController;
use App\Controllers\ChatController;
use App\Controllers\MessageController;

/*
|--------------------------------------------------------------------------
| Rotas Protegidas (Requer Autenticação)
|--------------------------------------------------------------------------
*/
Router::group(['middleware' => ['auth', 'throttle:120,1']], function () {
    
    // Dashboard / Página Principal do Chat
    Router::get('/', [HomeController::class, 'index'])->name('home.show');

    // Perfil / Página Perfil do Chat
    Router::get('/profile', [HomeController::class, 'profile'])->name('profile.show');

    // Perfil Edit / Página Edit do Chat
    Router::get('/profile/edit', [HomeController::class, 'profileEdit'])->name('profile.edit');

    // Definições / Página Definições do Chat
    Router::get('/settings', [HomeController::class, 'settings'])->name('settings.show');
    
    // Terminar Sessão
    Router::get('/logout', [AuthController::class, 'logout'])->name('logout.show');

    /**
     * Gestão de Amizades
     */
    Router::post('/friends/{id}/request', [FriendController::class, 'store'])->name('friends.request');
    Router::post('/friends/{id}/accept',  [FriendController::class, 'accept'])->name('friends.accept');
    Router::post('/friends/{id}/reject',  [FriendController::class, 'reject'])->name('friends.reject');
    Router::delete('/friends/{id}/remove',[FriendController::class, 'destroy'])->name('friends.destroy');

    /**
     * Mensagens e Histórico
     */
    Router::get('/messages/{id}', [MessageController::class, 'index'])->name('messages.history');
});

/*
|--------------------------------------------------------------------------
| Protocolo WebSocket
|--------------------------------------------------------------------------
*/
Router::websocket('/ws/chat', ChatController::class);

/*
|--------------------------------------------------------------------------
| Rotas Públicas (Apenas para Convidados / Guest)
|--------------------------------------------------------------------------
*/
Router::group(['middleware' => ['guest']], function(){
    
    // Visualização de Login e Registo
    Router::get('/sign-in', [AuthController::class, 'index'])->name('login.show');
    Router::get('/sign-up', [AuthController::class, 'shpwRegister'])->name('register.show');

    // Processamento de Dados (POST)
    Router::post('/register/users', [AuthController::class, 'store'])
            ->name('register')
            ->middleware('throttle:5,1');

    Router::post('/login/users', [AuthController::class, 'login'])
            ->name('login')
            ->middleware('throttle:3,1');
});
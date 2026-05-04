<?php

declare(strict_types=1);

namespace App\Controllers;

use Slenix\Http\Request;
use Slenix\Http\Response;

/**
 * HomeController
 *
 * Gerencia as rotas principais da interface do utilizador, incluindo o painel de chat,
 * configurações de conta e gestão de perfil.
 * 
 * Todas as rotas possuem um middleware manual de verificação de autenticação.
 *
 * @package App\Controllers
 */
class HomeController
{
    /**
     * Página principal do chat.
     *
     * Carrega os dados necessários para a sidebar:
     *  - Lista de amigos confirmados (enviados + recebidos).
     *  - Pedidos de amizade pendentes recebidos.
     *  - Sugestões de novos amigos.
     *
     * GET /
     *
     * @param Request  $req
     * @param Response $res
     * @return Response View renderizada ou redirect para o login.
     */
    public function index(Request $req, Response $res)
    {
        if (!auth()->check()) {
            $res->status(403)->redirect('/sign-in');
        }

        $user = auth()->user();
        $friends = $user->allFriends();
        $pending = $user->pendingRequests()->get();
        $suggestions = $user->suggestions();

        return view('pages.index', compact('user', 'friends', 'pending', 'suggestions'));
    }

    /**
     * Exibe a página de definições da conta.
     * 
     * Permite ao utilizador configurar preferências de privacidade, 
     * notificações e segurança da conta.
     * 
     * GET /settings
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function settings(Request $req, Response $res)
    {
         if (!auth()->check()) {
            $res->status(403)->redirect('/sign-in');
        }

        $user = auth()->user();
        $friends = $user->allFriends();
        $pending = $user->pendingRequests()->get();
        $suggestions = $user->suggestions();
        
        return view('pages.settings', compact('user', 'friends', 'pending', 'suggestions'));
    }

    /**
     * Exibe o perfil público/privado do utilizador autenticado.
     * 
     * Mostra informações detalhadas como Bio, localização e status,
     * além de manter os dados da sidebar actualizados.
     * 
     * GET /profile
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function profile(Request $req, Response $res)
    {
         if (!auth()->check()) {
            $res->status(403)->redirect('/sign-in');
        }

        $user = auth()->user();
        $friends = $user->allFriends();
        $pending = $user->pendingRequests()->get();
        $suggestions = $user->suggestions();
        
        return view('pages.profile', compact('user', 'friends', 'pending', 'suggestions'));
    }

    /**
     * Exibe o formulário de edição de perfil.
     * 
     * Fornece a interface para alteração de avatar, nome, username e bio.
     * Carrega a lista de amigos para manter a UI consistente durante a navegação.
     * 
     * GET /profile/edit
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function profileEdit(Request $req, Response $res)
    {
         if (!auth()->check()) {
            $res->status(403)->redirect('/sign-in');
        }

        $user = auth()->user();
        $friends = $user->allFriends();
        $pending = $user->pendingRequests()->get();
        $suggestions = $user->suggestions();

        $errors = session()->getFlash('errors') ?? [];
        
        return view('pages.profile-edit', compact('user', 'friends', 'pending', 'suggestions', 'errors'));
    }
}
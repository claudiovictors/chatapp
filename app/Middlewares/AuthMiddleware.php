<?php
/*
|--------------------------------------------------------------------------
| Classe AuthMiddleware
|--------------------------------------------------------------------------
|
| Este middleware [descreva a funcionalidade do middleware aqui].
|
*/

declare(strict_types=1);

namespace App\Middlewares;

use App\Models\User;
use Slenix\Libraries\Session;
use Slenix\Http\Message\Request;
use Slenix\Http\Message\Response;
use Slenix\Http\Message\Middleware;

class AuthMiddleware implements Middleware
{
    /**
     * Handle da requisição através do middleware.
     *
     * @param Request $request A requisição HTTP.
     * @param Response $response A resposta HTTP.
     * @param array $params Parâmetros da rota.
     * @return bool Retorna true para continuar, false para interromper.
     */
    public function handle(Request $request, Response $response, array $params): bool
    {
        // Lógica do middleware aqui
        $someCondition = Session::has('user_id');

        // Exemplo: verificar alguma condição
        if (!$someCondition) {

            redirect('/login'); // Redirecionar o usuário para a tela de login

            return false;
        }

        return true; // Continua a execução
    }
}

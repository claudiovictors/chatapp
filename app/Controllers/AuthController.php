<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Slenix\Http\Request;
use Slenix\Http\Response;
use Slenix\Supports\Libraries\Str;

/**
 * Classe AuthController
 * 
 * Gere os processos de autenticação, incluindo login, registo e encerramento de sessão.
 * 
 * @package App\Controllers
 */
class AuthController
{
    /**
     * Apresenta a página de login.
     *
     * @param Request $req Objeto da requisição.
     * @param Response $res Objeto da resposta.
     * @return mixed A view do formulário de login.
     */
    public function index(Request $req, Response $res)
    {
        return view('auth.login');
    }

    /**
     * Apresenta a página de registo de novos utilizadores.
     *
     * @param Request $req Objeto da requisição.
     * @param Response $res Objeto da resposta.
     * @return mixed A view do formulário de registo.
     */
    public function shpwRegister(Request $req, Response $res)
    {
        return view('auth.register');
    }

    /**
     * Processa a criação de uma nova conta de utilizador.
     *
     * @param Request $req Objeto da requisição.
     * @param Response $res Objeto da resposta.
     * @return mixed Redirecionamento com mensagem de sucesso ou erro.
     */
    public function store(Request $req, Response $res)
    {
        // Higienização dos dados de entrada
        $fname = Str::escape($req->input('fname'));
        $lname = Str::escape($req->input('lname'));
        $email = Str::escape($req->input('email'));
        $password = Str::escape($req->input('password'));

        // Validação de campos obrigatórios
        if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
            flash()->error('Por favor, preencha todos os campos obrigatórios!');
            return redirect()->back();
        }

        // Validação de formato de nome
        if (!validate_name($fname) || !validate_name($lname)) {
            flash()->error('Por favor, introduza um nome e apelido válidos!');
            return redirect()->back();
        }

        // Validação de endereço de e-mail
        if (!Str::isEmail($email)) {
            flash()->error('Por favor, introduza um endereço de e-mail válido!');
            return redirect()->back();
        }

        // Validação de segurança da palavra-passe
        if (Str::length($password) < 8) {
            flash()->error('A palavra-passe deve conter, no mínimo, 8 caracteres!');
            return redirect()->back();
        }

        // Verificação de duplicidade de e-mail
        if (User::where('email', $email)->exists()) {
            flash()->error('Este endereço de e-mail já se encontra registado!');
            return redirect()->back();
        }

        // Criação do utilizador no sistema
        $user = User::create([
            'fname' => $fname,
            'lname' => $lname,
            'email' => $email,
            'password' => hash_make($password),
            'is_active' => false,
            'status' => 'online'
        ]);

        if (!$user) {
            flash()->error('Ocorreu um erro inesperado durante o registo. Por favor, tente novamente!');
            return redirect()->back();
        }

        // Autentica o utilizador e redireciona para a página principal
        auth()->login($user);

        return redirect()->route('home.show');
    }

    /**
     * Processa a tentativa de autenticação do utilizador.
     *
     * @param Request $req Objeto da requisição.
     * @param Response $res Objeto da resposta.
     * @return mixed Redirecionamento para a home ou volta ao login em caso de falha.
     */
    public function login(Request $req, Response $res)
    {
        $email = Str::escape($req->input('email'));
        $password = Str::escape($req->input('password'));

        // Validação de preenchimento
        if (empty($email) || empty($password)) {
            flash()->error('Por favor, introduza as suas credenciais!');
            return redirect()->back();
        }

        // Verifica se o utilizador existe antes de tentar o login
        $user = User::where('email', $email)->first();

        if (!$user) {
            flash()->error('Não foi possível encontrar uma conta associada a este e-mail.');
            return redirect()->back();
        }

        // Tentativa de autenticação (Attempt)
        if (!auth()->attempt(['email' => $email, 'password' => $password])) {
            flash()->error('E-mail ou palavra-passe incorretos!');
            return redirect()->back();
        }

        return redirect()->route('home.show');
    }

    /**
     * Termina a sessão do utilizador atual.
     *
     * @param Request $req Objeto da requisição.
     * @param Response $res Objeto da resposta.
     * @return void
     */
    public function logout(Request $req, Response $res)
    {
        // Se não estiver autenticado, apenas redireciona
        if (!auth()->check()) {
            redirect()->route('login.show');
            return;
        }

        $user = auth()->user();
        $user->status = 'offline';
        $user->save();

        auth()->logout();
        redirect()->route('login.show');
    }
}
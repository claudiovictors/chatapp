<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Slenix\Http\Message\Request;
use Slenix\Http\Message\Response;
use Slenix\Http\Message\Upload;
use Slenix\Libraries\Session;

class AuthController
{
    /**
     * Renderização para a página de Register
     * @param \Slenix\Http\Message\Request $request
     * @param \Slenix\Http\Message\Response $response
     */
    public function showRegister(Request $request, Response $response)
    {
        return view('auth.register');
    }

    /**
     * Renderização para a página de Login
     * @param \Slenix\Http\Message\Request $request
     * @param \Slenix\Http\Message\Response $response
     */
    public function showLogin(Request $request, Response $response)
    {
        return view('auth.login');
    }

    /**
     * Registrar novos usuários
     * @param \Slenix\Http\Message\Request $request
     * @param \Slenix\Http\Message\Response $response
     */
    public function regiter(Request $request, Response $response)
    {
        $user_id = rand(time(), 10000000);
        $fname = sanetize($request->input('fname'));
        $lname = sanetize($request->input('lname'));
        $email = sanetize($request->input('email'));
        $password = sanetize($request->input('password'));
        $image = $_FILES['image'];

        Session::flashOldInput($request->all()); # Pega todos dados antigos

        # Verifica se os campos estão vázios ou não
        if (empty($fname) && empty($lname) && empty($email) && empty($password)) {
            Session::flash('error', 'Por favor preencha os campos vázios!');
            redirect('/');
        }

        if (!$image) {
            Session::flash('error', 'Por favor seleciona uma imagem!');
            redirect('/');
        }

        if (!sanetize($fname) && !sanetize($lname)) {
            Session::flash('error', 'Por favor digite o seu nome completo!');
            redirect('/');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Por favor digite um e-mail válido!');
            redirect('/');
        }

        if (strlen($password) < 6) {
            Session::flash('error', 'Senha deve ter pelo menos 6 caracteres!');
            redirect('/');
        }

        if (User::where('email', $email)->exists()) {
            Session::flash('error', 'E-mail já cadastrado!');
            redirect('/');
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $instanceUpload = new Upload($image);
        $nameImage = 'IMG-' . $instanceUpload->getHash() . '-' . date('Y-m-d') . '.' . $instanceUpload->getExtension();

        $instanceUpload->setAllowedExtensions(['png', 'jpg', 'jpeg', 'webp'])
            ->setMaxSize(2 * 1024 * 1024);

        if (!$instanceUpload->isValid()) {
            Session::flash('error', 'Imagem inválida! Use JPG/PNG até 2MB');
            return redirect('/');
        }

        if ($instanceUpload->store('assets/images/', $nameImage)) {
            $createUser = User::create([
                'id' => $user_id,
                'fname' => $fname,
                'lname' => $lname,
                'email' => $email,
                'password' => $passwordHash,
                'status' => 'Online',
                'image' => basename($nameImage)
            ]);

            if (!$createUser) {
                Session::flash('error', 'Erro ao carregar ao cadastrar');
                return redirect('/');
            }

            $selectUser = User::where('email', $email)->first();
            Session::set('user_id', $selectUser->id);
            return redirect('/~');
        } else {
            Session::flash('error', 'Erro ao carregar a imagem');
            return redirect('/');
        }
    }


    /**
     * Fazer login do usuário
     * @param \Slenix\Http\Message\Request $request
     * @param \Slenix\Http\Message\Response $response
     */
    public function login(Request $request, Response $response)
    {
        $email = sanetize($request->input('email'));
        $password = sanetize($request->input('password'));

        Session::flashOldInput($request->all()); # Pega todos dados antigos

        # Verifica se os campos estão vázios ou não
        if (!empty($email) && !empty($password)) {

            if (User::where('email', $email)->exists()) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    if (password_verify($password, $user->password)) {
                        $selectUser = User::where('email', $email)->first();
                        Session::set('user_id', $selectUser->id);
                        $selectUser->status = 'Online';
                        $selectUser->update();
                        return redirect('/~');
                    } else {
                        Session::flash('error', 'Senha inválido!');
                        redirect('/login');
                    }
                } else {
                    Session::flash('error', 'E-mail inválido!');
                    redirect('/login');
                }
            } else {
                Session::flash('error', 'E-mail ou senha inválido!');
                redirect('/login');
            }
        } else {
            Session::flash('error', 'Por favor preencha os campos vázios!');
            redirect('/login');
        }
    }
}

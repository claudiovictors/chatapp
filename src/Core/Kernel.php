<?php
/*
 |--------------------------------------------------------------------------
 | Classe Kernel
 |--------------------------------------------------------------------------
 |
 | O Kernel da aplicação é responsável por gerenciar o ciclo de vida da
 | aplicação, desde a inicialização até o despacho da requisição e
 | o tratamento de erros.
 |
 */

declare(strict_types=1);

namespace Slenix\Core;

use Slenix\Libraries\EnvLoad;
use Slenix\Http\Message\Router;
use Slenix\Libraries\Session;
use Slenix\Exceptions\ErrorHandler;

class Kernel
{
    /**
     * @var float Armazena o timestamp de quando a aplicação foi iniciada.
     */
    private float $startTime;

    /**
     * Construtor da classe Kernel.
     *
     * @param float $startTime O timestamp de quando a aplicação foi iniciada.
     */
    public function __construct(float $startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * Inicia e executa a aplicação.
     *
     * Este método configura os manipuladores de erro, carrega variáveis
     * de ambiente, inicia a sessão e despacha a requisição.
     *
     * @return void
     */
    public function run(): void
    {
        $errorHandler = new ErrorHandler();

        // Configura manipuladores de erro globais
        set_error_handler([$errorHandler, 'handleError']);
        set_exception_handler([$errorHandler, 'handleException']);
        
        Session::start();
        class_alias(Session::class, 'Session');

        try {
            EnvLoad::load(__DIR__ . '/../../.env');
        } catch (\Exception $e) {
            $errorHandler->handleEnvError($e);
        }

        require_once __DIR__ . '/../../routes/web.php';
        Router::dispatch();
    }
}
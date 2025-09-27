<?php
/*
 |--------------------------------------------------------------------------
 | Classe AppFactory
 |--------------------------------------------------------------------------
 |
 | Esta classe atua como uma fábrica para o núcleo da aplicação (Kernel),
 | criando e inicializando a aplicação de forma simplificada.
 |
 */
declare(strict_types=1);

namespace Slenix\Core;

class AppFactory
{
    /**
     * Cria e executa uma nova instância da aplicação.
     *
     * @param float $startTime O timestamp de quando a aplicação foi iniciada.
     * @return void
     */
    public static function create(float $startTime): void
    {
        (new Kernel($startTime))->run();
    }
}
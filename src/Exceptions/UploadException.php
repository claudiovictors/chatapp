<?php
/*
 |--------------------------------------------------------------------------
 | Classe UploadException
 |--------------------------------------------------------------------------
 |
 | Exceção personalizada para erros de upload de arquivos.
 |
 */

declare(strict_types=1);

namespace Slenix\Exceptions;

class UploadException extends \RuntimeException
{
    /**
     * @var array<int, string> Mapeamento de códigos de erro PHP para mensagens de erro.
     */
    protected static array $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'O arquivo excede o limite de tamanho definido em php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'O arquivo excede o limite de tamanho definido no formulário HTML.',
        UPLOAD_ERR_PARTIAL    => 'O upload do arquivo foi feito apenas parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo foi enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Faltando uma pasta temporária.',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever o arquivo em disco.',
        UPLOAD_ERR_EXTENSION  => 'Uma extensão do PHP interrompeu o upload do arquivo.',
    ];

    /**
     * Construtor da exceção de upload.
     *
     * @param int $code O código de erro de upload retornado pelo PHP.
     */
    public function __construct(int $code)
    {
        $message = self::$errorMessages[$code] ?? 'Ocorreu um erro desconhecido durante o upload.';
        parent::__construct($message, $code);
    }
}
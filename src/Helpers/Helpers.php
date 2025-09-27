<?php

declare(strict_types=1);

use Slenix\Http\Message\Response;
use Slenix\Libraries\Template;
use Slenix\Http\Message\Router;
use Slenix\Libraries\Session;
/* 
|--------------------------------------------|
|****** HELPERS GERAIS E CONSTANTES v1 ******|
|--------------------------------------------|
*/

// Define o tempo de início do script para cálculo de performance.
define('SLENIX_START', microtime(true));

// Constantes para os diretórios do projeto, baseadas na estrutura fornecida.
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('SRC_PATH', ROOT_PATH . '/src');
define('ROUTES_PATH', ROOT_PATH . '/routes');
define('VIEWS_PATH', ROOT_PATH . '/views');

/* 
|--------------------------------------------|
|****** FUNÇÕES PARA MANIPULAR STRINGS ******|
|--------------------------------------------|
*/

if (!function_exists('sanitize')):
    /**
     * Sanitiza uma string para evitar ataques XSS.
     *
     * @param string $string
     * @return string
     */
    function sanetize(string $string): string {
        return trim(htmlspecialchars($string, ENT_QUOTES, 'UTF-8'));
    }
endif;

if (!function_exists('validate')):
    /**
     * Valida se uma string contém apenas letras e espaços.
     *
     * @param string $string
     * @return mixed
     */
    function validate(string $string): mixed {
        return preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/u', $string);
    }
endif;

if (!function_exists('camel_case')) {
    /**
     * Converte uma string para o formato camelCase.
     *
     * @param string $string
     * @return string
     */
    function camel_case(string $string)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string))));
    }
}

if (!function_exists('snake_case')) {
    /**
     * Converte uma string para o formato snake_case.
     *
     * @param string $string
     * @param string $delimiter
     * @return string
     */
    function snake_case(string $string, string $delimiter = '_')
    {
        if (!ctype_lower($string)) {
            $string = preg_replace('/\s+/u', '', $string);
            $string = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $string), 'UTF-8');
        }
        return $string;
    }
}

if (!function_exists('str_default')) {
    /**
     * Retorna uma string padrão se a string fornecida for vazia ou nula.
     *
     * @param string|null $string
     * @param string $default
     * @return string
     */
    function str_default(?string $string, string $default)
    {
        return empty($string) ? $default : $string;
    }
}

if (!function_exists('limit')):
    /**
     * Limita o tamanho de uma string, adicionando reticências se necessário.
     *
     * @param string $text
     * @param int $limit
     * @return string
     */
    function limit(string $text, int $limit): string {
        return (strlen($text) >= $limit) ? substr($text, 0, $limit).'...' : $text;
    }
endif;

/* |---------------------------------------------|
|****** FUNÇÕES PARA MANIPULAR DADOS ******|
|---------------------------------------------|
*/

if (!function_exists('is_empty')):
    /**
     * Checa se um valor é considerado "vazio".
     *
     * @param mixed $value
     * @return bool
     */
    function is_empty(mixed $value): bool {
        return $value === null || $value === '' || $value === [];
    }
endif;

if (!function_exists('array_get')):
    /**
     * Obtém um valor de um array de forma segura, evitando erros.
     *
     * @param array $array
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    function array_get(array $array, string|int $key, mixed $default = null): mixed {
        return $array[$key] ?? $default;
    }
endif;

if (!function_exists('to_json')):
    /**
     * Converte um array ou objeto em uma string JSON.
     *
     * @param mixed $data
     * @return string
     */
    function to_json(mixed $data): string {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
endif;

if (!function_exists('from_json')):
    /**
     * Decodifica uma string JSON em um array ou objeto.
     *
     * @param string $json
     * @param bool $assoc
     * @return mixed
     */
    function from_json(string $json, bool $assoc = true): mixed {
        return json_decode($json, $assoc);
    }
endif;

/* 
|---------------------------------------------|
******** FUNÇÕES PARA MANIPULAR DATAS ********
|---------------------------------------------|
*/

if (!function_exists('now')):
    /**
     * Retorna um objeto de data e hora imutável para o momento atual.
     *
     * @return \DateTimeImmutable
     */
    function now(): \DateTimeImmutable {
        return new \DateTimeImmutable('now');
    }
endif;

if (!function_exists('format_date')):
    /**
     * Formata uma data para um formato específico.
     *
     * @param string $date_string
     * @param string $format
     * @return string|null
     */
    function format_date(string $date_string, string $format = 'd/m/Y H:i:s'): ?string {
        try {
            $date = new \DateTimeImmutable($date_string);
            return $date->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }
endif;


/* 
|--------------------------------------------|
|****** FUNÇÕES PARA MANIPULAR O LUNA *******|
|--------------------------------------------|
*/

if (!function_exists('env')):
    function env(string $key, mixed $default = null): string|int|bool|null {
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }
endif;

if (!function_exists('redirect')):
    function redirect(string $path): void {
        $response = new Response();
        $response->redirect($path);
    }
endif;

if (!function_exists('view')):
    function view(string $template, array $data = []) {
        $view_template = new Template($template, $data);
        echo $view_template->render();
    }
endif;

if (!function_exists('route')):
    /**
     * Gera a URL para uma rota nomeada.
     *
     * @param string $name O nome da rota.
     * @param array $params Parâmetros para substituir na URL.
     * @return string|null A URL gerada ou null se a rota não for encontrada.
     * @throws \Exception Se parâmetros obrigatórios estiverem faltando.
     */
    function route(string $name, array $params = []): ?string {
        return Router::route($name, $params);
    }
endif;

if (!function_exists('old')) {
    /**
     * Retorna o valor antigo de um campo de formulário.
     */
    function old(string $key, mixed $default = null): string {
        $value = \Slenix\Libraries\Session::getFlash('_old_input_' . $key, $default);
        return (string) ($value ?? '');
    }
}

Template::share('route', function (string $name, array $params = []): ?string {
    return Router::route($name, $params);
});


Template::share('Session', [
    'has' => function (string $key): bool {
        return Session::has($key);
    },
    'get' => function (string $key, mixed $default = null): mixed {
        return Session::get($key, $default);
    },
    'set' => function (string $key, mixed $value): void {
        Session::set($key, $value);
    },
    'flash' => function (string $key, mixed $value): void {
        Session::flash($key, $value);
    },
    'getFlash' => function (string $key, mixed $default = null): mixed {
        return Session::getFlash($key, $default);
    },
    'hasFlash' => function (string $key): bool {
        return Session::hasFlash($key);
    },
    'remove' => function (string $key): void {
        Session::remove($key);
    },
    'destroy' => function (): void {
        Session::destroy();
    }
]);

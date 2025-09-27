<?php
/*  
|--------------------------------------------------------------------------  
| Classe Response  
|--------------------------------------------------------------------------  
|  
| Esta classe é responsável por gerenciar as respostas HTTP da aplicação,  
| permitindo definir códigos de status, cabeçalhos, cookies, conteúdo em  
| diferentes formatos (JSON, XML, HTML, etc.) e redirecionamentos.  
|  
*/    

declare(strict_types=1);

namespace Slenix\Http\Message;

use InvalidArgumentException;
use RuntimeException;

class Response 
{
    private int $statusCode = 200;
    private mixed $content = '';
    private array $headers = [];
    private string $charset = 'utf-8';
    private ?string $contentType = null;
    private bool $hasBeenSent = false;
    private array $cookies = [];
    
    /**
     * @var array Mapeamento de códigos de status para mensagens
     */
    private static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * Define o código de status HTTP da resposta
     * @param int $statusCode Código de status HTTP
     * @return self
     * @throws InvalidArgumentException
     */
    public function status(int $statusCode = 200): self 
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException('Código de status deve estar entre 100 e 599');
        }
        
        $this->statusCode = $statusCode;
        
        if (!$this->hasBeenSent) {
            http_response_code($this->statusCode);
        }
        
        return $this;
    }

    /**
     * Envia uma resposta em formato JSON
     * @param mixed $data Os dados a serem convertidos para JSON
     * @param int $statusCode Código de status HTTP
     * @param int $jsonOptions Opções para json_encode
     * @return void
     */
    public function json(mixed $data, int $statusCode = 200, int $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): void
    {
        $this->status($statusCode)
             ->withContentType('application/json')
             ->setContent($data);
             
        $jsonData = json_encode($data, $jsonOptions);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Erro ao codificar JSON: ' . json_last_error_msg());
        }
        
        $this->send($jsonData);
    }

    /**
     * Envia uma resposta em texto
     * @param string $text O texto a ser enviado
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    public function write(string $text, int $statusCode = 200): void 
    {
        $this->status($statusCode)
             ->setContent($text)
             ->send($text);
    }

    /**
     * Envia uma resposta em HTML
     * @param string $html O HTML a ser enviado
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    public function html(string $html, int $statusCode = 200): void 
    {
        $this->status($statusCode)
             ->withContentType('text/html')
             ->setContent($html)
             ->send($html);
    }

    /**
     * Define o corpo da resposta como HTML (sem enviar).
     *
     * @param string $html
     * @return self
     */
    public function setHtml(string $html): self 
    {
        $this->withContentType('text/html');
        $this->content = $html;
        return $this;
    }

    /**
     * Define um cookie na resposta
     * @param string $name Nome do cookie
     * @param string $value Valor do cookie
     * @param int $expire Tempo de expiração
     * @param array $options Opções adicionais do cookie
     * @return self
     */
    public function withCookie(
        string $name, 
        string $value, 
        int $expire = 0, 
        array $options = []
    ): self {
        $defaultOptions = [
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax'
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        $this->cookies[$name] = [
            'value' => $value,
            'expire' => $expire,
            'options' => $options
        ];
        
        if (!$this->hasBeenSent) {
            setcookie($name, $value, [
                'expires' => $expire,
                'path' => $options['path'],
                'domain' => $options['domain'],
                'secure' => $options['secure'],
                'httponly' => $options['httponly'],
                'samesite' => $options['samesite']
            ]);
        }
        
        return $this;
    }

    /**
     * Define um cookie seguro na resposta
     * @param string $name Nome do cookie
     * @param string $value Valor do cookie
     * @param int $expire Tempo de expiração
     * @param array $options Opções adicionais para o cookie
     * @return self
     */
    public function withSecureCookie(
        string $name, 
        string $value, 
        int $expire = 0, 
        array $options = []
    ): self {
        $secureOptions = array_merge($options, [
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        return $this->withCookie($name, $value, $expire, $secureOptions);
    }

    /**
     * Remove um cookie
     * @param string $name Nome do cookie
     * @param array $options Opções do cookie para remoção
     * @return self
     */
    public function withoutCookie(string $name, array $options = []): self 
    {
        $defaultOptions = [
            'path' => '/',
            'domain' => ''
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        unset($this->cookies[$name]);
        
        if (!$this->hasBeenSent) {
            setcookie($name, '', [
                'expires' => time() - 3600,
                'path' => $options['path'],
                'domain' => $options['domain']
            ]);
        }
        
        return $this;
    }

    /**
     * Redireciona para outro caminho
     * @param string $path Caminho para redirecionamento
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    public function redirect(string $path, int $statusCode = 302): void 
    {
        if (!in_array($statusCode, [301, 302, 303, 307, 308])) {
            throw new InvalidArgumentException('Código de status de redirecionamento inválido');
        }
        
        $this->status($statusCode)
             ->withHeader('Location', $path)
             ->send();
    }

    /**
     * Redireciona de volta (usando HTTP_REFERER)
     * @param string $fallback URL de fallback se referer não estiver disponível
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    public function redirectBack(string $fallback = '/', int $statusCode = 302): void 
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        $this->redirect($referer, $statusCode);
    }

    /**
     * Define um header na resposta
     * @param string $name Nome do header
     * @param string $value Valor do header
     * @return self
     */
    public function withHeader(string $name, string $value): self 
    {
        $this->headers[trim($name)] = trim($value);
        return $this;
    }

    /**
     * Define múltiplos headers na resposta
     * @param array $headers Array associativo de headers
     * @return self
     */
    public function withHeaders(array $headers): self 
    {
        foreach ($headers as $name => $value) {
            $this->withHeader($name, $value);
        }
        return $this;
    }

    /**
     * Remove um header da resposta
     * @param string $name Nome do header
     * @return self
     */
    public function withoutHeader(string $name): self 
    {
        unset($this->headers[trim($name)]);
        return $this;
    }

    /**
     * Define o tipo de conteúdo
     * @param string $contentType
     * @param string|null $charset
     * @return self
     */
    public function withContentType(string $contentType, ?string $charset = null): self
    {
        $this->contentType = $contentType;
        if ($charset) {
            $this->charset = $charset;
        }
        return $this;
    }

    /**
     * Envia uma resposta em XML
     * @param string|\SimpleXMLElement $xml Conteúdo XML
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    public function xml($xml, int $statusCode = 200): void 
    {
        $content = is_string($xml) ? $xml : $xml->asXML();
        
        $this->status($statusCode)
             ->withContentType('application/xml')
             ->setContent($content)
             ->send($content);
    }

    /**
     * Envia um arquivo como download
     * @param string $filePath Caminho do arquivo
     * @param string|null $fileName Nome do arquivo para download
     * @param string|null $contentType Tipo de conteúdo
     * @param bool $inline Se true, exibe inline ao invés de download
     * @return void
     */
    public function download(
        string $filePath, 
        ?string $fileName = null, 
        ?string $contentType = null,
        bool $inline = false
    ): void {
        if (!file_exists($filePath)) {
            $this->status(404)->json(['error' => 'Arquivo não encontrado']);
        }

        if (!is_readable($filePath)) {
            $this->status(403)->json(['error' => 'Arquivo não pode ser lido']);
        }

        $fileName = $fileName ?? basename($filePath);
        $contentType = $contentType ?? mime_content_type($filePath) ?: 'application/octet-stream';
        $disposition = $inline ? 'inline' : 'attachment';

        $this->withHeaders([
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition . '; filename="' . $fileName . '"',
            'Content-Length' => (string) filesize($filePath),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);

        $this->sendHeaders();
        
        // Lê o arquivo em chunks para economizar memória
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
            fclose($handle);
        }
        
        $this->hasBeenSent = true;
        exit;
    }

    /**
     * Renderiza um template com dados
     * @param string $template Caminho do template
     * @param array $data Dados para o template
     * @param int $statusCode Código de status HTTP
     * @return mixed
     */
    public function render(string $template, array $data = [], int $statusCode = 200)
    {
        $this->status($statusCode);
        
        if (function_exists('view')) {
            return view($template, $data);
        }
        
        // Fallback simples para renderização de template
        $templatePath = $this->findTemplate($template);
        if (!$templatePath) {
            throw new RuntimeException("Template '{$template}' não encontrado");
        }
        
        extract($data);
        ob_start();
        include $templatePath;
        $content = ob_get_clean();
        
        $this->html($content, $statusCode);
        return $content;
    }

    /**
     * Encontra o caminho do template
     * @param string $template
     * @return string|null
     */
    private function findTemplate(string $template): ?string
    {
        $possiblePaths = [
            "views/{$template}.php",
            "resources/views/{$template}.php",
            "{$template}.php"
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }

    /**
     * Envia uma resposta em formato JSONP
     * @param string $callback Nome da função de callback
     * @param mixed $data Dados a serem enviados
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    public function jsonp(string $callback, mixed $data, int $statusCode = 200): void 
    {
        // Sanitiza o nome do callback para segurança
        $callback = preg_replace('/[^a-zA-Z0-9_$.]/', '', $callback);
        
        if (empty($callback)) {
            throw new InvalidArgumentException('Nome de callback inválido');
        }
        
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Erro ao codificar JSON: ' . json_last_error_msg());
        }
        
        $this->status($statusCode)
             ->withContentType('application/javascript')
             ->send("{$callback}({$json});");
    }

    /**
     * Envia uma resposta de erro padronizada
     * @param string $message Mensagem de erro
     * @param int $statusCode Código de status HTTP
     * @param array $details Detalhes adicionais do erro
     * @return void
     */
    public function error(string $message, int $statusCode = 500, array $details = []): void
    {
        $errorData = [
            'error' => true,
            'message' => $message,
            'status_code' => $statusCode,
            'status_text' => self::$statusTexts[$statusCode] ?? 'Unknown Status'
        ];
        
        if (!empty($details)) {
            $errorData['details'] = $details;
        }
        
        $this->json($errorData, $statusCode);
    }

    /**
     * Envia uma resposta de sucesso padronizada
     * @param mixed $data Dados a serem enviados
     * @param string $message Mensagem de sucesso
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    public function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        $successData = [
            'success' => true,
            'message' => $message,
            'status_code' => $statusCode
        ];
        
        if ($data !== null) {
            $successData['data'] = $data;
        }
        
        $this->json($successData, $statusCode);
    }

    /**
     * Envia todas as cabeçalhos definidos
     * @return self
     */
    public function sendHeaders(): self 
    {
        if ($this->hasBeenSent) {
            return $this;
        }
        
        // Define Content-Type se especificado
        if ($this->contentType !== null) {
            header('Content-Type: ' . $this->contentType . '; charset=' . $this->charset);
        }
        
        // Envia headers personalizados
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        
        return $this;
    }

    /**
     * Envia a resposta com os cabeçalhos definidos
     * @param mixed $content Conteúdo a ser enviado
     * @param int|null $statusCode Código de status HTTP
     * @return void
     */
    public function send(mixed $content = null, ?int $statusCode = null): void 
    {
        if ($this->hasBeenSent) {
            return;
        }
        
        if ($statusCode !== null) {
            $this->status($statusCode);
        }
        
        if ($content !== null) {
            $this->content = $content;
        }
        
        $this->sendHeaders();
        
        // Processa o conteúdo baseado no tipo
        if (is_array($this->content) || is_object($this->content)) {
            echo json_encode($this->content, JSON_UNESCAPED_UNICODE);
        } else {
            echo $this->content;
        }
        
        $this->hasBeenSent = true;
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        
        exit;
    }

    /**
     * Define o conteúdo da resposta
     * @param mixed $content
     * @return self
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Obtém o código de status HTTP da resposta
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Obtém o texto do status HTTP
     * @return string
     */
    public function getStatusText(): string
    {
        return self::$statusTexts[$this->statusCode] ?? 'Unknown Status';
    }

    /**
     * Obtém o corpo da resposta
     * @return mixed
     */
    public function getBody(): mixed
    {
        return $this->content;
    }

    /**
     * Obtém um header específico
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getHeader(string $name, mixed $default = null): mixed
    {
        return $this->headers[trim($name)] ?? $default;
    }

    /**
     * Obtém todos os headers
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Obtém todos os cookies
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Verifica se a resposta já foi enviada
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->hasBeenSent;
    }

    /**
     * Define headers de cache
     * @param int $maxAge Tempo máximo de cache em segundos
     * @param bool $public Se o cache é público
     * @return self
     */
    public function withCache(int $maxAge = 3600, bool $public = true): self
    {
        $cacheControl = $public ? 'public' : 'private';
        $cacheControl .= ', max-age=' . $maxAge;
        
        $this->withHeaders([
            'Cache-Control' => $cacheControl,
            'Expires' => gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT',
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT'
        ]);
        
        return $this;
    }

    /**
     * Define headers para não fazer cache
     * @return self
     */
    public function withoutCache(): self
    {
        $this->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
        
        return $this;
    }

    /**
     * Define headers de CORS
     * @param array $options Opções de CORS
     * @return self
     */
    public function withCors(array $options = []): self
    {
        $defaults = [
            'origin' => '*',
            'methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'headers' => 'Content-Type, Authorization, X-Requested-With',
            'credentials' => false,
            'max_age' => 86400
        ];
        
        $options = array_merge($defaults, $options);
        
        $headers = [
            'Access-Control-Allow-Origin' => $options['origin'],
            'Access-Control-Allow-Methods' => $options['methods'],
            'Access-Control-Allow-Headers' => $options['headers'],
            'Access-Control-Max-Age' => (string) $options['max_age']
        ];
        
        if ($options['credentials']) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }
        
        $this->withHeaders($headers);
        
        return $this;
    }

    /**
     * Cria uma nova instância de Response
     * @param mixed $content
     * @param int $statusCode
     * @return self
     */
    public static function make(mixed $content = '', int $statusCode = 200): self
    {
        return (new self())->setContent($content)->status($statusCode);
    }
}
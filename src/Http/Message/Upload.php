<?php
/*
|--------------------------------------------------------------------------
| Classe Upload Profissional
|--------------------------------------------------------------------------
|
| Uma classe Upload inspirada no Laravel mas simplificada para micro
| frameworks. Mantém API profissional com métodos padrão da indústria.
|
| USO BÁSICO:
| $upload = Upload::from('image');
| $path = $upload->store('uploads');
| echo $upload->getOriginalName();
|
*/

declare(strict_types=1);

namespace Slenix\Http\Message;

use RuntimeException;
use InvalidArgumentException;
use Slenix\Exceptions\UploadException;

class Upload
{
    private array $file;
    private ?string $storedPath = null;
    
    // Configurações padrão
    private int $maxSize = 10485760; // 10MB
    private array $allowedMimeTypes = [];
    private array $allowedExtensions = [];
    private array $forbiddenExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8',
        'exe', 'bat', 'cmd', 'scr', 'com', 'pif', 'vbs', 'js', 'html'
    ];

    // Cache de informações
    private ?string $detectedMimeType = null;
    private ?array $imageInfo = null;

    public function __construct(array $file)
    {
        $this->validateFileArray($file);
        $this->file = $file;
    }

    // ========== FACTORY METHODS ==========

    /**
     * Cria instância a partir do nome do campo do formulário
     *
     * @param string $fieldName Nome do campo no formulário
     * @return self|null
     * @throws UploadException
     */
    public static function from(string $fieldName): ?self
    {
        if (!isset($_FILES[$fieldName])) {
            return null;
        }

        $file = $_FILES[$fieldName];
        
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new UploadException($file['error']);
        }

        return new self($file);
    }

    /**
     * Cria múltiplas instâncias
     *
     * @param array $fieldNames
     * @return array
     */
    public static function multiple(array $fieldNames): array
    {
        $uploads = [];
        foreach ($fieldNames as $fieldName) {
            $upload = self::from($fieldName);
            if ($upload) {
                $uploads[$fieldName] = $upload;
            }
        }
        return $uploads;
    }

    /**
     * Retorna todos os uploads da requisição
     *
     * @return array
     */
    public static function all(): array
    {
        $uploads = [];
        foreach ($_FILES as $fieldName => $file) {
            $upload = self::from($fieldName);
            if ($upload) {
                $uploads[$fieldName] = $upload;
            }
        }
        return $uploads;
    }

    // ========== INFORMAÇÕES DO ARQUIVO ==========

    /**
     * Retorna o nome original do arquivo
     *
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->file['name'] ?? '';
    }

    /**
     * Retorna apenas o nome sem extensão
     *
     * @return string
     */
    public function getBaseName(): string
    {
        return pathinfo($this->getOriginalName(), PATHINFO_FILENAME);
    }

    /**
     * Retorna a extensão do arquivo
     *
     * @return string
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->getOriginalName(), PATHINFO_EXTENSION));
    }

    /**
     * Retorna o tamanho do arquivo em bytes
     *
     * @return int
     */
    public function getSize(): int
    {
        return (int) ($this->file['size'] ?? 0);
    }

    /**
     * Retorna o tamanho formatado para humanos
     *
     * @param int $precision
     * @return string
     */
    public function getHumanReadableSize(int $precision = 2): string
    {
        $bytes = $this->getSize();
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Retorna o tipo MIME real do arquivo
     *
     * @return string
     */
    public function getMimeType(): string
    {
        if ($this->detectedMimeType === null) {
            if (!file_exists($this->getRealPath())) {
                throw new RuntimeException('Arquivo temporário não encontrado');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                throw new RuntimeException('Não foi possível inicializar detecção de MIME type');
            }

            $mimeType = finfo_file($finfo, $this->getRealPath());
            finfo_close($finfo);

            if ($mimeType === false) {
                // Fallback para tipo informado pelo cliente
                $this->detectedMimeType = $this->file['type'] ?? 'application/octet-stream';
            } else {
                $this->detectedMimeType = $mimeType;
            }
        }

        return $this->detectedMimeType;
    }

    /**
     * Retorna o tipo MIME informado pelo cliente
     *
     * @return string|null
     */
    public function getClientMimeType(): ?string
    {
        return $this->file['type'] ?? null;
    }

    /**
     * Retorna o caminho do arquivo temporário
     *
     * @return string
     */
    public function getRealPath(): string
    {
        return $this->file['tmp_name'] ?? '';
    }

    /**
     * Retorna o código de erro do upload
     *
     * @return int
     */
    public function getError(): int
    {
        return (int) ($this->file['error'] ?? UPLOAD_ERR_NO_FILE);
    }

    /**
     * Retorna o hash SHA-256 do arquivo
     *
     * @return string
     */
    public function getHash(): string
    {
        return hash_file('sha256', $this->getRealPath());
    }

    /**
     * Retorna informações da imagem (se for imagem)
     *
     * @return array|null
     */
    public function getImageInfo(): ?array
    {
        if ($this->imageInfo === null && $this->isImage()) {
            $info = getimagesize($this->getRealPath());
            if ($info !== false) {
                $this->imageInfo = [
                    'width' => $info[0],
                    'height' => $info[1],
                    'type' => $info[2],
                    'html' => $info[3],
                    'mime' => $info['mime'],
                    'channels' => $info['channels'] ?? null,
                    'bits' => $info['bits'] ?? null,
                ];
            }
        }

        return $this->imageInfo;
    }

    // ========== VERIFICAÇÕES ==========

    /**
     * Verifica se é uma imagem
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with($this->getMimeType(), 'image/');
    }

    /**
     * Verifica se é um documento
     *
     * @return bool
     */
    public function isDocument(): bool
    {
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv'
        ];
        
        return in_array($this->getMimeType(), $documentMimes);
    }

    /**
     * Verifica se é um vídeo
     *
     * @return bool
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->getMimeType(), 'video/');
    }

    /**
     * Verifica se é um áudio
     *
     * @return bool
     */
    public function isAudio(): bool
    {
        return str_starts_with($this->getMimeType(), 'audio/');
    }

    /**
     * Verifica se o arquivo é válido
     *
     * @return bool
     */
    public function isValid(): bool
    {
        try {
            $this->validate();
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    // ========== VALIDAÇÕES ==========

    /**
     * Valida o arquivo contra as regras configuradas
     *
     * @return void
     * @throws RuntimeException
     */
    public function validate(): void
    {
        $this->validateSize();
        $this->validateMimeType();
        $this->validateExtension();
        $this->validateSecurity();
    }

    /**
     * Define tamanho máximo permitido
     *
     * @param int $maxSize Tamanho em bytes
     * @return self
     */
    public function setMaxSize(int $maxSize): self
    {
        if ($maxSize <= 0) {
            throw new InvalidArgumentException('Tamanho máximo deve ser maior que zero');
        }
        $this->maxSize = $maxSize;
        return $this;
    }

    /**
     * Define tipos MIME permitidos
     *
     * @param array $mimeTypes
     * @return self
     */
    public function setAllowedMimeTypes(array $mimeTypes): self
    {
        $this->allowedMimeTypes = $mimeTypes;
        return $this;
    }

    /**
     * Define extensões permitidas
     *
     * @param array $extensions
     * @return self
     */
    public function setAllowedExtensions(array $extensions): self
    {
        $this->allowedExtensions = array_map('strtolower', $extensions);
        return $this;
    }

    // ========== ARMAZENAMENTO ==========

    /**
     * Armazena o arquivo no diretório especificado
     *
     * @param string $directory
     * @param string|null $filename
     * @return string Caminho do arquivo armazenado
     * @throws RuntimeException
     */
    public function store(string $directory, ?string $filename = null): string
    {
        $this->validate();
        
        $this->ensureDirectoryExists($directory);
        
        $filename = $filename ?: $this->generateUniqueFilename();
        $filename = $this->sanitizeFilename($filename);
        
        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        
        // Evita sobrescrita
        $path = $this->makeUniqueFilePath($path);
        
        if (!move_uploaded_file($this->getRealPath(), $path)) {
            throw new RuntimeException('Falha ao mover arquivo para destino');
        }
        
        chmod($path, 0644);
        $this->storedPath = $path;
        
        return $path;
    }

    /**
     * Armazena com nome único automático
     *
     * @param string $directory
     * @return string
     */
    public function storeAs(string $directory): string
    {
        return $this->store($directory);
    }

    /**
     * Move o arquivo armazenado para outro local
     *
     * @param string $newPath
     * @return bool
     * @throws RuntimeException
     */
    public function move(string $newPath): bool
    {
        if (!$this->storedPath || !file_exists($this->storedPath)) {
            throw new RuntimeException('Arquivo não foi armazenado ainda');
        }
        
        $directory = dirname($newPath);
        $this->ensureDirectoryExists($directory);
        
        if (rename($this->storedPath, $newPath)) {
            $this->storedPath = $newPath;
            return true;
        }
        
        return false;
    }

    /**
     * Cria uma cópia do arquivo
     *
     * @param string $destination
     * @return bool
     */
    public function copy(string $destination): bool
    {
        $source = $this->storedPath ?: $this->getRealPath();
        
        if (!file_exists($source)) {
            return false;
        }
        
        $directory = dirname($destination);
        $this->ensureDirectoryExists($directory);
        
        return copy($source, $destination);
    }

    /**
     * Remove o arquivo armazenado
     *
     * @return bool
     */
    public function delete(): bool
    {
        if ($this->storedPath && file_exists($this->storedPath)) {
            $result = unlink($this->storedPath);
            if ($result) {
                $this->storedPath = null;
            }
            return $result;
        }
        return false;
    }

    /**
     * Retorna o caminho onde o arquivo foi armazenado
     *
     * @return string|null
     */
    public function getStoredPath(): ?string
    {
        return $this->storedPath;
    }

    // ========== MÉTODOS AUXILIARES ==========

    /**
     * Retorna conteúdo do arquivo como string
     *
     * @return string
     */
    public function getContent(): string
    {
        $path = $this->storedPath ?: $this->getRealPath();
        
        if (!file_exists($path)) {
            throw new RuntimeException('Arquivo não encontrado');
        }
        
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Não foi possível ler o arquivo');
        }
        
        return $content;
    }

    /**
     * Salva conteúdo em arquivo
     *
     * @param string $content
     * @return bool
     */
    public function putContent(string $content): bool
    {
        if (!$this->storedPath) {
            throw new RuntimeException('Arquivo não foi armazenado ainda');
        }
        
        return file_put_contents($this->storedPath, $content) !== false;
    }

    // ========== MÉTODOS PRIVADOS ==========

    private function validateFileArray(array $file): void
    {
        $required = ['name', 'tmp_name', 'size', 'error'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $file)) {
                throw new InvalidArgumentException("Chave obrigatória '{$key}' não encontrada");
            }
        }
        
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Arquivo não foi enviado via upload HTTP');
        }
    }

    private function validateSize(): void
    {
        if ($this->getSize() > $this->maxSize) {
            throw new RuntimeException(
                "Arquivo excede o tamanho máximo de " . $this->formatBytes($this->maxSize)
            );
        }
        
        if ($this->getSize() <= 0) {
            throw new RuntimeException('Arquivo está vazio');
        }
    }

    private function validateMimeType(): void
    {
        if (empty($this->allowedMimeTypes)) {
            return;
        }
        
        $mimeType = $this->getMimeType();
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new RuntimeException(
                "Tipo MIME '{$mimeType}' não permitido. Permitidos: " . 
                implode(', ', $this->allowedMimeTypes)
            );
        }
    }

    private function validateExtension(): void
    {
        $extension = $this->getExtension();
        
        // Verifica extensões proibidas
        if (in_array($extension, $this->forbiddenExtensions)) {
            throw new RuntimeException("Extensão '{$extension}' não permitida por segurança");
        }
        
        // Verifica extensões permitidas
        if (!empty($this->allowedExtensions) && !in_array($extension, $this->allowedExtensions)) {
            throw new RuntimeException(
                "Extensão '{$extension}' não permitida. Permitidas: " . 
                implode(', ', $this->allowedExtensions)
            );
        }
    }

    private function validateSecurity(): void
    {
        // Verifica extensões duplas
        $filename = $this->getOriginalName();
        if (preg_match('/\.(' . implode('|', $this->forbiddenExtensions) . ')\./i', $filename)) {
            throw new RuntimeException('Arquivo com extensão dupla perigosa detectado');
        }
        
        // Para imagens, valida se realmente é uma imagem
        if ($this->isImage()) {
            $imageInfo = getimagesize($this->getRealPath());
            if ($imageInfo === false) {
                throw new RuntimeException('Arquivo não é uma imagem válida');
            }
        }
    }

    private function sanitizeFilename(string $filename): string
    {
        // Remove caracteres perigosos
        $filename = preg_replace('/[^\w\-_\.]/', '_', $filename);
        
        // Remove pontos múltiplos
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limita tamanho
        if (strlen($filename) > 100) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 90) . '.' . $ext;
        }
        
        return $filename;
    }

    private function generateUniqueFilename(): string
    {
        return date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $this->getExtension();
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new RuntimeException("Não foi possível criar diretório: {$directory}");
            }
        }
        
        if (!is_writable($directory)) {
            throw new RuntimeException("Diretório não é gravável: {$directory}");
        }
    }

    private function makeUniqueFilePath(string $path): string
    {
        if (!file_exists($path)) {
            return $path;
        }
        
        $directory = dirname($path);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        $counter = 1;
        do {
            $newPath = $directory . DIRECTORY_SEPARATOR . $filename . '_' . $counter . '.' . $extension;
            $counter++;
        } while (file_exists($newPath));
        
        return $newPath;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes >= 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
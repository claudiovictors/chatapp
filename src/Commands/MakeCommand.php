<?php

declare(strict_types=1);

namespace Slenix\Commands;

class MakeCommand extends Command
{
    private array $args;

    public function __construct(array $args)
    {
        $this->args = $args;
    }

    public function makeModel(): void
    {
        if (count($this->args) < 3) {
            self::error('Nome do model é obrigatório.');
            self::info('Exemplo: php celestial make:model User');
            exit(1);
        }

        $modelName = ucfirst($this->args[2]);
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName)) . 's';
        $filePath = __DIR__ . '/../../app/Models/' . $modelName . '.php';

        $this->ensureFileDoesNotExist($filePath, $modelName, 'Model');

        $template = <<<EOT
<?php

declare(strict_types=1);

namespace App\Models;

use Slenix\Database\Model;

class {$modelName} extends Model
{
    protected string \$table = '{$tableName}';
    protected string \$primaryKey = 'id';
    protected array \$fillable = [];
}
EOT;

        $this->createFile($filePath, $template, $modelName, 'Model');
    }


    public function makeController(): void
    {
        if (count($this->args) < 3) {
            self::error('Nome do controller é obrigatório.');
            self::info('Exemplo: php celestial make:controller Home');
            self::info('Para resource controller: php celestial make:controller Home --resource');
            exit(1);
        }

        // Verifica se tem a flag --resource
        $isResource = in_array('--resource', $this->args);

        // Se tem --resource, usa o método específico
        if ($isResource) {
            $this->makeControllerResource();
            return;
        }

        // Remove a posição do --resource se existir para pegar o nome corretamente
        $controllerName = ucfirst($this->getControllerName());
        $filePath = __DIR__ . '/../../app/Controllers/' . $controllerName . '.php';

        $this->ensureFileDoesNotExist($filePath, $controllerName, 'Controller');

        $template = <<<EOT
<?php

declare(strict_types=1);

namespace App\Controllers;

use Slenix\Http\Message\Request;
use Slenix\Http\Message\Response;

class {$controllerName}
{
    public function index(Request \$request, Response \$response)
    {
        // A sua lógica a aplicação
    }
}
EOT;

        $this->createFile($filePath, $template, $controllerName, 'Controller');
    }

    public function makeControllerResource(): void
    {
        $controllerName = ucfirst($this->getControllerName());
        $filePath = __DIR__ . '/../../app/Controllers/' . $controllerName . '.php';

        $this->ensureFileDoesNotExist($filePath, $controllerName, 'Controller');

        $template = <<<EOT
<?php

declare(strict_types=1);

namespace App\Controllers;

use Slenix\Http\Message\Request;
use Slenix\Http\Message\Response;

class {$controllerName}
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request \$request, Response \$response)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request \$request, Response \$response)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request \$request, Response \$response)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request \$request, Response \$response, array \$params)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request \$request, Response \$response, array \$params)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request \$request, Response \$response, array \$params)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request \$request, Response \$response, array \$params)
    {
        //
    }
}
EOT;

        $this->createFile($filePath, $template, $controllerName, 'Controller');
    }

    /**
     * Obtém o nome do controller dos argumentos, ignorando flags
     */
    private function getControllerName(): string
    {
        // Procura por argumentos que não sejam flags (não começam com --)
        for ($i = 2; $i < count($this->args); $i++) {
            if (!str_starts_with($this->args[$i], '--')) {
                return $this->args[$i];
            }
        }

        self::error('Nome do controller é obrigatório.');
        self::info('Exemplo: php celestial make:controller Home');
        self::info('Para resource controller: php celestial make:controller Home --resource');
        exit(1);
    }

    private function ensureFileDoesNotExist(string $path, string $name, string $type): void
    {
        if (file_exists($path)) {
            self::error("O {$type} '{$name}' já existe em {$path}.");
            exit(1);
        }

        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            self::error("Não foi possível criar o diretório {$dir}.");
            exit(1);
        }
    }

    public function makeMiddleware(): void
    {
        if (count($this->args) < 3) {
            self::error('Nome do middleware é obrigatório.');
            self::info('Exemplo: php celestial make:middleware Auth');
            exit(1);
        }

        $middlewareName = ucfirst($this->args[2]);
        if (!str_ends_with($middlewareName, 'Middleware')) {
            $middlewareName .= 'Middleware';
        }

        $filePath = __DIR__ . '/../../app/Middlewares/' . $middlewareName . '.php';

        $this->ensureFileDoesNotExist($filePath, $middlewareName, 'Middleware');

        $template = <<<EOT
<?php
/*
|--------------------------------------------------------------------------
| Classe {$middlewareName}
|--------------------------------------------------------------------------
|
| Este middleware [descreva a funcionalidade do middleware aqui].
|
*/

declare(strict_types=1);

namespace App\Middlewares;

use Slenix\Http\Message\Middleware;
use Slenix\Http\Message\Request;
use Slenix\Http\Message\Response;
use Slenix\Libraries\Session;

class {$middlewareName} implements Middleware
{
    /**
     * Handle da requisição através do middleware.
     *
     * @param Request \$request A requisição HTTP.
     * @param Response \$response A resposta HTTP.
     * @param array \$params Parâmetros da rota.
     * @return bool Retorna true para continuar, false para interromper.
     */
    public function handle(Request \$request, Response \$response, array \$params): bool
    {
        // Lógica do middleware aqui
        
        // Exemplo: verificar alguma condição
        // if (!\$someCondition) {
        //     \$response->status(403)->json(['error' => 'Forbidden']);
        //     return false;
        // }

        return true; // Continua a execução
    }
}
EOT;

        $this->createFile($filePath, $template, $middlewareName, 'Middleware');

        self::info('Para usar este middleware, registre-o nas suas rotas:');
        self::info("Route::get('/exemplo', [Controller::class, 'method'])->middleware('{$middlewareName}');");
    }

    private function createFile(string $path, string $content, string $name, string $type): void
    {
        if (file_put_contents($path, $content) === false) {
            self::error("Falha ao criar {$type} '{$name}' em {$path}.");
            exit(1);
        }

        self::success("{$type} '{$name}' criado com sucesso em:");
        echo "  {$path}" . PHP_EOL;
    }
}

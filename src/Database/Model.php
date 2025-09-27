<?php

/** 
 * |--------------------------------------------------------------------------
 * | SLENIX MODEL - Abstrata para implementação de Active Record Pattern
 * |--------------------------------------------------------------------------
 * |
 * | Fornece funcionalidades básicas de CRUD e consultas para modelos de dados.
 * | Integra QueryBuilder para consultas fluentes e elegantes.
 * | Todas as classes modelo devem estender esta classe e definir a propriedade $table.
 * |
 * | @package Slenix\Database
 * | @author Slenix
 * | @version 2.0
 */

declare(strict_types=1);

namespace Slenix\Database;

use PDO, PDOStatement;

abstract class Model
{
    /** @var string Nome da tabela no banco de dados */
    protected string $table = '';

    /** @var string Nome da chave primária */
    protected string $primaryKey = 'id';

    /** @var array Atributos do modelo */
    protected array $attributes = [];

    /** @var array Atributos modificados (dirty attributes) */
    protected array $dirty = [];

    /** @var PDO Instância da conexão com o banco */
    protected PDO $pdo;

    /** @var array Atributos que devem ser ocultados na serialização */
    protected array $hidden = [];

    /** @var array Atributos que podem ser preenchidos em massa */
    protected array $fillable = [];

    /** @var array Atributos protegidos contra preenchimento em massa */
    protected array $guarded = [];

    /** @var array Casts de tipos para atributos */
    protected array $casts = [];

    /** @var bool Se o modelo usa timestamps automáticos */
    protected bool $timestamps = true;

    /** @var string Nome da coluna created_at */
    protected string $createdAt = 'created_at';

    /** @var string Nome da coluna updated_at */
    protected string $updatedAt = 'updated_at';

    /** @var array Relacionamentos carregados */
    protected array $relations = [];

    /**
     * Construtor da classe
     * 
     * @param array $attributes Atributos iniciais do modelo
     * @throws \Exception Se a propriedade $table não estiver definida
     */
    public function __construct(array $attributes = [])
    {
        if (empty($this->table)) {
            throw new \Exception('A propriedade $table deve ser definida na classe modelo.');
        }

        $this->pdo = Database::getInstance();

        if (!empty($attributes)) {
            $this->fillAttributes($attributes);
        }
    }

    /**
     * Preenche atributos sem afetar dirty state (usado no construtor e quando carrega do banco)
     * 
     * @param array $attributes Atributos para preencher
     * @return void
     */
    protected function fillAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $this->castAttribute($key, $value);
        }
    }

    /**
     * NOVO: Método para hidratar modelo vindo do banco (usado pelo QueryBuilder)
     * 
     * @param array $attributes
     * @return static
     */
    public static function hydrate(array $attributes): self
    {
        $instance = new static();
        $instance->fillAttributes($attributes);
        return $instance;
    }

    /**
     * Setter mágico para definir atributos
     * 
     * @param string $name Nome do atributo
     * @param mixed $value Valor do atributo
     */
    public function __set(string $name, $value): void
    {
        $this->setAttribute($name, $value);
    }

    /**
     * Getter mágico para obter atributos OU relações
     * 
     * @param string $name Nome do atributo ou relação
     * @return mixed Valor do atributo ou relação, ou null se não existir
     */
    public function __get(string $name)
    {
        // Primeiro, tenta atributo próprio
        if (array_key_exists($name, $this->attributes)) {
            return $this->castAttribute($name, $this->attributes[$name]);
        }

        // Se é uma relação (método que retorna Relation), carrega lazy se não estiver
        if (method_exists($this, $name)) {
            $method = new \ReflectionMethod($this, $name);
            $returnType = $method->getReturnType();
            
            if ($returnType && 
                $returnType instanceof \ReflectionNamedType && 
                str_contains($returnType->getName(), 'Relation')) {
                
                if (!isset($this->relations[$name])) {
                    $this->relations[$name] = $this->$name()->getResults();
                }
                return $this->relations[$name];
            }
        }

        return null;
    }

    /**
     * Método mágico para chamadas estáticas (Query Builder)
     * 
     * @param string $method Nome do método
     * @param array $parameters Parâmetros do método
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        $queryBuilder = static::newQuery();
        if (!method_exists($queryBuilder, $method)) {
            throw new \BadMethodCallException("Método '$method' não existe no QueryBuilder.");
        }
        return $queryBuilder->$method(...$parameters);
    }

    /**
     * Cria nova instância do QueryBuilder
     * 
     * @return QueryBuilder Nova instância do query builder
     */
    public static function newQuery(): QueryBuilder
    {
        $instance = new static();
        if (empty($instance->table)) {
            throw new \Exception('A propriedade $table deve ser definida na classe modelo.');
        }

        return new QueryBuilder(Database::getInstance(), $instance->table, static::class);
    }

    /**
     * Define um atributo com cast e dirty tracking
     * 
     * @param string $key Nome do atributo
     * @param mixed $value Valor do atributo
     * @return void
     */
    protected function setAttribute(string $key, $value): void
    {
        $castValue = $this->castAttribute($key, $value);

        // Verifica se o valor mudou
        $hasChanged = !array_key_exists($key, $this->attributes) || $this->attributes[$key] !== $castValue;

        $this->attributes[$key] = $castValue;

        // Só adiciona ao dirty se não for chave primária E se o valor mudou
        if ($key !== $this->primaryKey && $hasChanged) {
            $this->dirty[$key] = $castValue;
        }
    }

    /**
     * Aplica cast de tipo para um atributo
     * 
     * @param string $key Nome do atributo
     * @param mixed $value Valor do atributo
     * @return mixed Valor com cast aplicado
     */
    protected function castAttribute(string $key, $value)
    {
        if (!isset($this->casts[$key]) || $value === null) {
            return $value;
        }

        $castType = $this->casts[$key];

        try {
            return match ($castType) {
                'int', 'integer' => $this->castToInteger($value),
                'float', 'double' => $this->castToFloat($value),
                'bool', 'boolean' => $this->castToBoolean($value),
                'string' => $this->castToString($value),
                'json', 'array' => $this->castToJson($value, $key),
                'datetime' => $this->castToDateTime($value),
                default => $value
            };
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                "Erro ao aplicar cast '{$castType}' ao atributo '{$key}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Cast para integer
     * 
     * @param mixed $value
     * @return int
     */
    private function castToInteger($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }
        throw new \InvalidArgumentException("Valor não pode ser convertido para integer");
    }

    /**
     * Cast para float
     * 
     * @param mixed $value
     * @return float
     */
    private function castToFloat($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        throw new \InvalidArgumentException("Valor não pode ser convertido para float");
    }

    /**
     * Cast para boolean
     * 
     * @param mixed $value
     * @return bool
     */
    private function castToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (bool) $value;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on']);
        }
        return (bool) $value;
    }

    /**
     * Cast para string
     * 
     * @param mixed $value
     * @return string
     */
    private function castToString($value): string
    {
        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return (string) $value;
        }
        throw new \InvalidArgumentException("Valor não pode ser convertido para string");
    }

    /**
     * Cast para JSON/array
     * 
     * @param mixed $value
     * @param string $key
     * @return array
     */
    private function castToJson($value, string $key): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException("Valor deve ser string ou array para cast JSON");
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("JSON inválido: " . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Cast para DateTime
     * 
     * @param mixed $value
     * @return \DateTime
     */
    private function castToDateTime($value): \DateTime
    {
        if ($value instanceof \DateTime) {
            return $value;
        }
        if (is_string($value)) {
            return new \DateTime($value);
        }
        if (is_int($value)) {
            return new \DateTime('@' . $value);
        }
        throw new \InvalidArgumentException("Valor não pode ser convertido para DateTime");
    }

    /**
     * Método público para acessar fill
     * 
     * @param array $data Dados para preencher
     * @return $this
     */
    public function fill(array $data): self
    {
        foreach ($data as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Verifica se um atributo pode ser preenchido em massa
     * 
     * @param string $key Nome do atributo
     * @return bool True se pode ser preenchido
     */
    protected function isFillable(string $key): bool
    {
        // Se fillable está definido, só permite os campos listados
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }

        // Se guarded está definido, bloqueia os campos listados
        if (!empty($this->guarded)) {
            return !in_array($key, $this->guarded);
        }

        // Por padrão, permite todos os campos
        return true;
    }

    /**
     * Cria uma nova instância do modelo com os dados fornecidos
     * 
     * @param array $data Dados para criar o modelo
     * @return static Nova instância do modelo
     */
    public static function create(array $data): self
    {
        $instance = new static();
        $instance->fill($data);
        $instance->save();
        return $instance;
    }

    /**
     * Salva o modelo no banco de dados (insert ou update)
     * 
     * @return bool True se salvou com sucesso, false caso contrário
     */
    public function save(): bool
    {
        $isNewRecord = empty($this->attributes[$this->primaryKey]);

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');

            if ($isNewRecord) {
                $this->setAttribute($this->createdAt, $now);
            }

            $this->setAttribute($this->updatedAt, $now);
        }

        return $isNewRecord ? $this->performInsert() : $this->performUpdate();
    }

    /**
     * Insere um novo registro no banco de dados
     * 
     * @return bool True se inseriu com sucesso, false caso contrário
     */
    protected function performInsert(): bool
    {
        if (empty($this->attributes)) {
            return false;
        }

        $columns = array_keys($this->attributes);
        $columnsList = implode(',', $columns);
        $placeholders = ':' . implode(',:', $columns);

        $sql = "INSERT INTO {$this->table} ($columnsList) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($this->attributes);

        if ($result) {
            $lastId = $this->pdo->lastInsertId();
            if ($lastId) {
                $this->attributes[$this->primaryKey] = $lastId;
            }
            $this->dirty = [];
        }

        return $result;
    }

    /**
     * Atualiza o registro existente no banco de dados
     * 
     * @return bool True se atualizou com sucesso, false caso contrário
     */
    protected function performUpdate(): bool
    {
        if (empty($this->dirty) || empty($this->attributes[$this->primaryKey])) {
            return true; // Nada para atualizar
        }

        $updates = array_map(fn($key) => "$key = :$key", array_keys($this->dirty));
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE {$this->primaryKey} = :{$this->primaryKey}";

        $stmt = $this->pdo->prepare($sql);
        $params = array_merge($this->dirty, [$this->primaryKey => $this->attributes[$this->primaryKey]]);
        $result = $stmt->execute($params);

        if ($result) {
            $this->dirty = [];
        }

        return $result;
    }

    /**
     * Alias público para performUpdate (mantém compatibilidade)
     * 
     * @return bool
     */
    public function update(): bool
    {
        return $this->performUpdate();
    }

    /**
     * Deleta o registro do banco de dados
     * 
     * @return bool True se deletou com sucesso, false caso contrário
     */
    public function delete(): bool
    {
        if (empty($this->attributes[$this->primaryKey])) {
            return false;
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :{$this->primaryKey}";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$this->primaryKey => $this->attributes[$this->primaryKey]]);
    }

    /**
     * Busca um registro por ID
     * 
     * @param mixed $id ID do registro
     * @return static|null Instância do modelo ou null se não encontrado
     */
    public static function find($id): ?self
    {
        return static::where(static::make()->primaryKey, '=', $id)->first();
    }

    /**
     * Método estático melhorado para get() que retorna apenas o primeiro resultado
     * quando você quer apenas um registro
     * 
     * @param string $column Nome da coluna
     * @param mixed $value Valor para comparação
     * @return static|null Primeira instância do modelo ou null
     */
    public static function firstWhere(string $column, $value): ?self
    {
        return static::where($column, '=', $value)->first();
    }

    /**
     * Busca um registro por ID ou falha
     * 
     * @param mixed $id ID do registro
     * @return static Instância do modelo
     * @throws \Exception Se o registro não for encontrado
     */
    public static function findOrFail($id): self
    {
        $result = static::find($id);

        if ($result === null) {
            throw new \Exception("Registro com ID '$id' não encontrado na tabela.");
        }

        return $result;
    }

    /**
     * Busca múltiplos registros por IDs
     * 
     * @param array $ids Array de IDs
     * @return array Array de instâncias do modelo
     */
    public static function findMany(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return static::whereIn(static::make()->primaryKey, $ids)->get();
    }

    /**
     * Busca todos os registros da tabela
     * 
     * @return array Array de instâncias do modelo
     */
    public static function all(): array
    {
        return static::newQuery()->get();
    }

    /**
     * Busca o primeiro registro da tabela
     * 
     * @return static|null Instância do modelo ou null
     */
    public static function first(): ?self
    {
        return static::newQuery()->first();
    }

    /**
     * Busca o último registro da tabela (ordenado por chave primária)
     * 
     * @return static|null Instância do modelo ou null
     */
    public static function last(): ?self
    {
        $instance = static::make();
        return static::orderBy($instance->primaryKey, 'DESC')->first();
    }

    /**
     * Conta o número total de registros na tabela
     * 
     * @return int Número de registros
     */
    public static function count(): int
    {
        return static::newQuery()->count();
    }

    /**
     * Verifica se existe algum registro na tabela
     * 
     * @return bool True se existir pelo menos um registro
     */
    public static function exists(): bool
    {
        return static::count() > 0;
    }

    /**
     * Cria uma nova instância do modelo sem salvar
     * 
     * @return static Nova instância
     */
    protected static function make(): self
    {
        return new static();
    }

    /**
     * Busca registros com condições WHERE
     * 
     * @param string $column Nome da coluna
     * @param string|null $operator Operador de comparação
     * @param mixed $value Valor para comparação
     * @return QueryBuilder Instância do QueryBuilder
     */
    public static function where(string $column, $operator = null, $value = null): QueryBuilder
    {
        return static::newQuery()->where($column, $operator, $value);
    }

    /**
     * Busca registros com condição OR WHERE
     * 
     * @param string $column Nome da coluna
     * @param string|null $operator Operador de comparação
     * @param mixed $value Valor para comparação
     * @return QueryBuilder
     */
    public static function orWhere(string $column, $operator = null, $value = null): QueryBuilder
    {
        return static::newQuery()->orWhere($column, $operator, $value);
    }

    /**
     * Busca registros com WHERE IN
     * 
     * @param string $column Nome da coluna
     * @param array $values Array de valores
     * @return QueryBuilder
     */
    public static function whereIn(string $column, array $values): QueryBuilder
    {
        return static::newQuery()->whereIn($column, $values);
    }

    /**
     * Busca registros com WHERE NOT IN
     * 
     * @param string $column Nome da coluna
     * @param array $values Array de valores
     * @return QueryBuilder
     */
    public static function whereNotIn(string $column, array $values): QueryBuilder
    {
        return static::newQuery()->whereNotIn($column, $values);
    }

    /**
     * Busca registros com WHERE BETWEEN
     * 
     * @param string $column Nome da coluna
     * @param mixed $min Valor mínimo
     * @param mixed $max Valor máximo
     * @return QueryBuilder
     */
    public static function whereBetween(string $column, $min, $max): QueryBuilder
    {
        return static::newQuery()->whereBetween($column, $min, $max);
    }

    /**
     * Busca registros com WHERE IS NULL
     * 
     * @param string $column Nome da coluna
     * @return QueryBuilder
     */
    public static function whereNull(string $column): QueryBuilder
    {
        return static::newQuery()->whereNull($column);
    }

    /**
     * Busca registros com WHERE IS NOT NULL
     * 
     * @param string $column Nome da coluna
     * @return QueryBuilder
     */
    public static function whereNotNull(string $column): QueryBuilder
    {
        return static::newQuery()->whereNotNull($column);
    }

    /**
     * Define colunas para seleção
     * 
     * @param array|string $columns Colunas para selecionar
     * @return QueryBuilder
     */
    public static function select($columns = ['*']): QueryBuilder
    {
        return static::newQuery()->select($columns);
    }

    /**
     * Adiciona DISTINCT à consulta
     * 
     * @return QueryBuilder
     */
    public static function distinct(): QueryBuilder
    {
        return static::newQuery()->distinct();
    }

    /**
     * Adiciona ORDER BY à consulta
     * 
     * @param string $column Nome da coluna
     * @param string $direction Direção (ASC/DESC)
     * @return QueryBuilder
     */
    public static function orderBy(string $column, string $direction = 'ASC'): QueryBuilder
    {
        return static::newQuery()->orderBy($column, $direction);
    }

    /**
     * Adiciona ORDER BY descendente
     * 
     * @param string $column Nome da coluna
     * @return QueryBuilder
     */
    public static function orderByDesc(string $column): QueryBuilder
    {
        return static::newQuery()->orderByDesc($column);
    }

    /**
     * Adiciona GROUP BY à consulta
     * 
     * @param string|array $columns Colunas para agrupamento
     * @return QueryBuilder
     */
    public static function groupBy($columns): QueryBuilder
    {
        return static::newQuery()->groupBy($columns);
    }

    /**
     * Busca todos os registros e retorna como arrays associativos
     * 
     * @return array Array de arrays associativos
     */
    public static function allArray(): array
    {
        return static::newQuery()->getArray();
    }

    /**
     * Busca o primeiro registro e retorna como array associativo
     * 
     * @return array|null Array associativo ou null
     */
    public static function firstArray(): ?array
    {
        return static::newQuery()->firstArray();
    }

    /**
     * Adiciona HAVING à consulta
     * 
     * @param string $column Nome da coluna
     * @param string $operator Operador de comparação
     * @param mixed $value Valor para comparação
     * @return QueryBuilder
     */
    public static function having(string $column, string $operator, $value): QueryBuilder
    {
        return static::newQuery()->having($column, $operator, $value);
    }

    /**
     * Define limite de registros
     * 
     * @param int $limit Número máximo de registros
     * @return QueryBuilder
     */
    public static function limit(int $limit): QueryBuilder
    {
        return static::newQuery()->limit($limit);
    }

    /**
     * Define offset para paginação
     * 
     * @param int $offset Número de registros para pular
     * @return QueryBuilder
     */
    public static function offset(int $offset): QueryBuilder
    {
        return static::newQuery()->offset($offset);
    }

    /**
     * Atalho para limit e offset (paginação)
     * 
     * @param int $perPage Registros por página
     * @param int $page Página atual (inicia em 1)
     * @return QueryBuilder
     */
    public static function take(int $perPage, int $page = 1): QueryBuilder
    {
        return static::newQuery()->take($perPage, $page);
    }

    /**
     * Adiciona INNER JOIN à consulta
     * 
     * @param string $table Tabela para join
     * @param string $first Primeira coluna
     * @param string $operator Operador de comparação
     * @param string $second Segunda coluna
     * @return QueryBuilder
     */
    public static function join(string $table, string $first, string $operator, string $second): QueryBuilder
    {
        return static::newQuery()->join($table, $first, $operator, $second);
    }

    /**
     * Adiciona LEFT JOIN à consulta
     * 
     * @param string $table Tabela para join
     * @param string $first Primeira coluna
     * @param string $operator Operador de comparação
     * @param string $second Segunda coluna
     * @return QueryBuilder
     */
    public static function leftJoin(string $table, string $first, string $operator, string $second): QueryBuilder
    {
        return static::newQuery()->leftJoin($table, $first, $operator, $second);
    }

    /**
     * Implementa paginação com metadados
     * 
     * @param int $perPage Registros por página
     * @param int $page Página atual
     * @return array Array com dados paginados e metadados
     */
    public static function paginate(int $perPage = 15, int $page = 1): array
    {
        return static::newQuery()->paginate($perPage, $page);
    }

    /**
     * Converte o modelo para array (incluindo relações)
     * 
     * @return array Array com todos os atributos (exceto os ocultos) + relações
     */
    public function toArray(): array
    {
        $array = $this->attributes;

        // Remove atributos ocultos
        foreach ($this->hidden as $hidden) {
            unset($array[$hidden]);
        }

        // Adiciona relações
        foreach ($this->relations as $key => $value) {
            if (is_array($value)) {
                $array[$key] = array_map(fn($item) => $item instanceof self ? $item->toArray() : $item, $value);
            } elseif ($value instanceof self) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * Converte o modelo para JSON
     * 
     * @return string JSON string
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * NOVO: Implementa JsonSerializable para funcionar automaticamente com json_encode()
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Recarrega o modelo do banco de dados
     * 
     * @return bool True se recarregou com sucesso
     */
    public function refresh(): bool
    {
        if (empty($this->attributes[$this->primaryKey])) {
            return false;
        }

        $fresh = static::find($this->attributes[$this->primaryKey]);

        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->relations = [];
            $this->dirty = [];
            return true;
        }

        return false;
    }

    /**
     * Verifica se um atributo específico foi modificado
     * 
     * @param string|null $key Nome do atributo (null para verificar qualquer)
     * @return bool True se o atributo foi modificado
     */
    public function isDirty(string $key = null): bool
    {
        if ($key === null) {
            return !empty($this->dirty);
        }

        return array_key_exists($key, $this->dirty);
    }

    /**
     * Obtém os atributos modificados
     * 
     * @return array Array com atributos modificados
     */
    public function getDirty(): array
    {
        return $this->dirty;
    }

    /**
     * Verifica se o modelo é novo (não salvo no banco)
     * 
     * @return bool True se é um modelo novo
     */
    public function isNew(): bool
    {
        return empty($this->attributes[$this->primaryKey]);
    }

    /**
     * Clona o modelo (replica sem chave primária)
     * 
     * @return static Nova instância com os mesmos atributos
     */
    public function replicate(): self
    {
        $attributes = $this->attributes;
        unset($attributes[$this->primaryKey]); // Remove a chave primária

        if ($this->timestamps) {
            unset($attributes[$this->createdAt], $attributes[$this->updatedAt]);
        }

        return new static($attributes);
    }

    /**
     * Obtém a chave primária do modelo
     * 
     * @return mixed Valor da chave primária
     */
    public function getKey()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * Obtém o nome da chave primária
     * 
     * @return string Nome da chave primária
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    /**
     * Obtém o nome da tabela
     * 
     * @return string Nome da tabela
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Obtém todos os atributos do modelo
     * 
     * @return array Array com todos os atributos
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Executa uma consulta personalizada
     * 
     * @param string $sql Query SQL
     * @param array $params Parâmetros para a query
     * @return array Array de instâncias do modelo
     */
    public static function query(string $sql, array $params = []): array
    {
        $instance = new static();
        $stmt = $instance->pdo->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = static::hydrate($data); // Usando hydrate ao invés de fillAttributes
        }

        return $results;
    }

    /**
     * Trunca a tabela (remove todos os registros)
     * 
     * @return bool True se executou com sucesso
     */
    public static function truncate(): bool
    {
        $instance = new static();
        $sql = "TRUNCATE TABLE {$instance->table}";
        return $instance->pdo->exec($sql) !== false;
    }

    /**
     * Define uma relação (usado pelo QueryBuilder no eager loading)
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setRelation(string $key, $value): self
    {
        $this->relations[$key] = $value;
        return $this;
    }

    /**
     * Carrega relacionamentos eager (com colunas específicas opcionais)
     * 
     * @param string|array $relations Nomes das relações, ex: 'posts' ou ['posts:title,content']
     * @return QueryBuilder
     */
    public static function with($relations): QueryBuilder
    {
        $relationsToLoad = is_array($relations) ? $relations : func_get_args();

        return static::newQuery()->withRelations($relationsToLoad);
    }

    /**
     * Define um relacionamento HasOne (um-para-um)
     * 
     * @param string $related Nome da classe do modelo relacionado
     * @param string|null $foreignKey Chave estrangeira (padrão: parentTable_id)
     * @param string|null $localKey Chave local (padrão: id)
     * @return HasOne
     */
    protected function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null)
    {
        $foreignKey = $foreignKey ?? $this->getTable() . '_id';
        $localKey = $localKey ?? $this->primaryKey;
        return new HasOne(new $related(), $this, $foreignKey, $localKey);
    }

    /**
     * Define um relacionamento BelongsTo (muitos-para-um)
     * 
     * @param string $related Nome da classe do modelo relacionado (parent)
     * @param string $foreignKey Chave estrangeira no modelo atual
     * @param string|null $ownerKey Chave do owner (padrão: id)
     * @return BelongsTo
     */
    protected function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null)
    {
        $relatedInstance = new $related();
        $foreignKey = $foreignKey ?? strtolower($this->getClassBasename($related)) . '_id';
        $ownerKey = $ownerKey ?? $relatedInstance->getKeyName();
        return new BelongsTo($relatedInstance, $this, $foreignKey, $ownerKey);
    }

    /**
     * Define um relacionamento HasMany (um-para-muitos)
     * 
     * @param string $related Nome da classe do modelo relacionado
     * @param string|null $foreignKey Chave estrangeira (padrão: parentTable_id)
     * @param string|null $localKey Chave local (padrão: id)
     * @return HasMany
     */
    protected function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null)
    {
        $foreignKey = $foreignKey ?? $this->getTable() . '_id';
        $localKey = $localKey ?? $this->primaryKey;
        return new HasMany(new $related(), $this, $foreignKey, $localKey);
    }

    /**
     * Obtém o nome da classe sem namespace (helper para relacionamentos)
     * 
     * @param string $class Nome completo da classe
     * @return string Nome da classe sem namespace
     */
    private function getClassBasename(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }
}
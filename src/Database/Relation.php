<?php

declare(strict_types=1);

namespace Slenix\Database;

abstract class Relation
{
    protected Model $related;
    protected Model $parent;
    protected string $foreignKey;
    protected string $localKey;
    protected QueryBuilder $query;

    public function __construct(Model $related, Model $parent, string $foreignKey, string $localKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        
        // Inicializa a query base
        $this->query = $this->related::newQuery();
        $this->addConstraints();
    }

    abstract public function addConstraints(): void;

    abstract public function match(array $models, array $results, string $relation): array;

    /**
     * Getter para modelo relacionado
     */
    public function getRelated(): Model
    {
        return $this->related;
    }

    /**
     * Getter para chave estrangeira
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Getter para chave local
     */
    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    /**
     * Getter para a query
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * Executa a query da relação
     */
    abstract public function getResults(array $columns = ['*']);
}
<?php

/**
 * |--------------------------------------------------------------------------
 * | HAS MANY - Relação de Banco de Dados
 * |--------------------------------------------------------------------------
 * |
 * | Classe que representa a relação "has many" (tem muitos) entre dois modelos.
 * | Esta classe define a lógica para carregar uma coleção de modelos relacionados.
 * |
 * | @package Slenix\Database
 * | @author Slenix
 * | @version 2.0
 */

declare(strict_types=1);

namespace Slenix\Database;

/**
 * Class HasMany
 *
 * Representa uma relação "has many" (tem muitos) entre dois modelos.
 * É usada para definir uma relação onde o modelo atual é o "pai"
 * de uma coleção de modelos relacionados (ex: um Post tem muitos Comments).
 *
 * @extends Relation
 */
class HasMany extends Relation
{
    /**
     * Adiciona as restrições à consulta da relação.
     *
     * Adiciona uma cláusula WHERE que une a chave estrangeira do modelo relacionado
     * (`$this->foreignKey`) com a chave primária do modelo pai (`$this->localKey`).
     *
     * @return void
     */
    public function addConstraints(): void
    {
        if (!empty($this->parent->{$this->localKey})) {
            $this->query->where($this->foreignKey, '=', $this->parent->{$this->localKey});
        }
    }

    /**
     * Associa os resultados da relação aos modelos individuais.
     *
     * Este método é usado para eager loading de uma relação `HasMany`.
     *
     * @param array $models Modelos pais que precisam ser relacionados.
     * @param array $results Resultados da consulta da relação.
     * @param string $relation O nome da relação.
     * @return array Modelos com a relação associada como um array.
     */
    public function match(array $models, array $results, string $relation): array
    {
        // Cria um dicionário para acesso rápido aos resultados.
        // A chave é o valor da chave estrangeira, e o valor é um array de modelos relacionados.
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->foreignKey}][] = $result;
        }

        // Itera sobre os modelos pais e associa a coleção de modelos relacionados do dicionário.
        foreach ($models as $model) {
            $localKeyValue = $model->getKey();
            $model->setRelation($relation, $dictionary[$localKeyValue] ?? []);
        }

        return $models;
    }

    /**
     * Executa a consulta da relação e retorna um array de resultados.
     *
     * Este método é usado para carregamento lento (`lazy loading`).
     *
     * @param array $columns Colunas a serem selecionadas na consulta.
     * @return array A coleção de modelos relacionados.
     */
    public function getResults(array $columns = ['*']): array
    {
        if (empty($this->parent->{$this->localKey})) {
            return [];
        }

        return $this->query->select($columns)->get();
    }

    /**
     * Adiciona uma condição `WHERE` à consulta da relação.
     *
     * Permite filtrar os modelos relacionados.
     *
     * @param string $column A coluna para a condição.
     * @param string|null $operator O operador de comparação (opcional).
     * @param mixed|null $value O valor a ser comparado (opcional).
     * @return self A instância atual da classe para encadeamento.
     */
    public function where(string $column, $operator = null, $value = null): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Adiciona uma cláusula `ORDER BY` à consulta da relação.
     *
     * Permite ordenar os modelos relacionados.
     *
     * @param string $column A coluna para ordenação.
     * @param string $direction A direção da ordenação ('ASC' ou 'DESC').
     * @return self A instância atual da classe para encadeamento.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Adiciona uma cláusula `LIMIT` à consulta da relação.
     *
     * Limita o número de modelos relacionados retornados.
     *
     * @param int $limit O número máximo de resultados.
     * @return self A instância atual da classe para encadeamento.
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }
}

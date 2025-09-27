<?php

/**
 * |--------------------------------------------------------------------------
 * | BELONGS TO - Relação de Banco de Dados
 * |--------------------------------------------------------------------------
 * |
 * | Classe que representa a relação "belongs to" (pertence a) entre dois modelos.
 * | Esta classe define a lógica para carregar um modelo relacionado.
 * |
 * | @package Slenix\Database
 * | @author Slenix
 * | @version 2.0
 */

declare(strict_types=1);

namespace Slenix\Database;

/**
 * Class BelongsTo
 *
 * Representa uma relação "belongs to" (pertence a) entre dois modelos.
 * Esta classe é usada para definir uma relação onde o modelo atual "pertence"
 * a um modelo pai (ex: um Post pertence a um User).
 *
 * @extends Relation
 */
class BelongsTo extends Relation
{
    /**
     * Adiciona as restrições à consulta da relação.
     *
     * Adiciona uma cláusula WHERE que une a chave primária do modelo relacionado
     * (defined by `$this->localKey`) com a chave estrangeira do modelo pai
     * (defined by `$this->foreignKey`).
     *
     * @return void
     */
    public function addConstraints(): void
    {
        if (!empty($this->parent->{$this->foreignKey})) {
            $this->query->where($this->localKey, '=', $this->parent->{$this->foreignKey});
        }
    }

    /**
     * Associa os resultados da relação a modelos individuais.
     *
     * Este método é usado para carregar uma relação `BelongsTo` em modelos já existentes.
     *
     * @param array $models Modelos pais que precisam ser relacionados.
     * @param array $results Resultados da consulta da relação.
     * @param string $relation O nome da relação.
     * @return array Modelos com a relação associada.
     */
    public function match(array $models, array $results, string $relation): array
    {
        // Cria um dicionário (hash map) para acesso rápido aos resultados da relação.
        // A chave é a chave primária do modelo relacionado (`ownerKey`), o valor é o modelo.
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->getKey()] = $result;
        }

        // Itera sobre os modelos pais e associa o modelo relacionado do dicionário.
        foreach ($models as $model) {
            $foreignKeyValue = $model->{$this->foreignKey};
            $model->setRelation($relation, $dictionary[$foreignKeyValue] ?? null);
        }

        return $models;
    }

    /**
     * Executa a consulta da relação e retorna um único resultado.
     *
     * Este método é usado para carregamento lento (`lazy loading`) da relação.
     *
     * @param array $columns Colunas a serem selecionadas na consulta.
     * @return mixed O modelo relacionado ou `null` se não for encontrado.
     */
    public function getResults(array $columns = ['*'])
    {
        if (empty($this->parent->{$this->foreignKey})) {
            return null;
        }

        return $this->query->select($columns)->first();
    }

    /**
     * Associa múltiplos modelos com os resultados da relação.
     *
     * Este método é otimizado para eager loading de múltiplos modelos.
     *
     * @param array $models Modelos pais.
     * @param array $results Resultados da consulta para eager loading.
     * @param string $relation O nome da relação.
     * @return array Modelos com a relação associada.
     */
    public function matchMany(array $models, array $results, string $relation): array
    {
        // Cria um dicionário para acesso rápido aos resultados.
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->getKey()] = $result;
        }

        // Associa os resultados aos modelos.
        foreach ($models as $model) {
            $foreignKeyValue = $model->{$this->foreignKey};
            $model->setRelation($relation, $dictionary[$foreignKeyValue] ?? null);
        }

        return $models;
    }
}

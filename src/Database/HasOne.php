<?php

/**
 * |--------------------------------------------------------------------------
 * | HAS ONE - Relação de Banco de Dados
 * |--------------------------------------------------------------------------
 * |
 * | Classe que representa a relação "has one" (tem um) entre dois modelos.
 * | Esta classe define a lógica para carregar um único modelo relacionado.
 * |
 * | @package Slenix\Database
 * | @author Slenix
 * | @version 2.0
 */

declare(strict_types=1);

namespace Slenix\Database;

/**
 * Class HasOne
 *
 * Representa uma relação "has one" (tem um) entre dois modelos.
 * É usada para definir uma relação onde o modelo atual "tem um"
 * modelo relacionado (ex: um User tem um Phone).
 *
 * @extends Relation
 */
class HasOne extends Relation
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
     * Este método é usado para eager loading de uma relação `HasOne`.
     *
     * @param array $models Modelos pais que precisam ser relacionados.
     * @param array $results Resultados da consulta da relação.
     * @param string $relation O nome da relação.
     * @return array Modelos com a relação associada.
     */
    public function match(array $models, array $results, string $relation): array
    {
        // Dicionário: chave = foreignKey value, valor = model relacionado
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->foreignKey}] = $result;
        }

        foreach ($models as $model) {
            $localKeyValue = $model->getKey();
            $model->setRelation($relation, $dictionary[$localKeyValue] ?? null);
        }

        return $models;
    }

    /**
     * Executa a consulta da relação e retorna um único resultado.
     *
     * Este método é usado para carregamento lento (`lazy loading`).
     *
     * @param array $columns Colunas a serem selecionadas na consulta.
     * @return mixed O modelo relacionado ou `null` se não for encontrado.
     */
    public function getResults(array $columns = ['*'])
    {
        if (empty($this->parent->{$this->localKey})) {
            return null;
        }

        return $this->query->select($columns)->first();
    }
}

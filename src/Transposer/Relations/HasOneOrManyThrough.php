<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Exceptions\UnresolvableHasOneException;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\ModelQueryBuilder;
use Brickhouse\Support\Collection;

/**
 * @template TRelatedModel of Model
 * @template TIntermediateModel of Model
 *
 * @extends Relation<TRelatedModel>
 */
abstract class HasOneOrManyThrough extends Relation
{
    /**
     * @param class-string<TRelatedModel>       $related
     * @param class-string<TIntermediateModel>  $intermediate
     */
    public function __construct(
        public readonly string $related,
        public readonly string $intermediate,
    ) {
        parent::__construct($this->related);
    }

    public function match(Collection $rows): Collection
    {
        $keyColumn = $this->related::key();

        $keys = [];
        foreach ($rows as $row) {
            $keys[] = $row[$keyColumn];
        }

        $relatedTable = $this->related::table();
        $modelKey = $this->parent::table() . '.' . $this->parent::key();

        $query = $this->prepareQueryBuilder($this->query);

        $query->builder
            ->select([$relatedTable . '.*', "{$modelKey} AS '{$modelKey}'"])
            ->whereIn($modelKey, $keys);

        $models = $query
            ->all()
            ->groupBy($modelKey);

        foreach ($rows->keys() as $idx) {
            $row = $rows[$idx];
            $modelId = $row[$keyColumn];
            $row[$this->property] = $models[$modelId];
            $rows[$idx] = $row;
        }

        return $rows;
    }

    /**
     * @inheritdoc
     */
    public function guessMatchingRelation(string|Model $model): string
    {
        foreach (new $this->related()->getModelRelations() as $property => $relation) {
            if (!$relation instanceof BelongsTo) {
                continue;
            }

            if ($relation->model !== $this->intermediate) {
                continue;
            }

            return $property;
        }

        throw new \RuntimeException("Failed to determine matching BelongsTo relation on " . $this->intermediate);
    }

    /**
     * Applies the constraints for all required joins to resolve the related model.
     *
     * @param ModelQueryBuilder<TRelatedModel>      $builder
     *
     * @return ModelQueryBuilder<TRelatedModel>
     */
    protected function prepareQueryBuilder(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        $this->getQueryForIntermediateTable($builder);
        $this->getQueryForParentTable($builder);

        return $builder;
    }

    /**
     * Applies the constraints for a join with the intermediate table.
     *
     * @param ModelQueryBuilder<TRelatedModel>      $builder
     *
     * @return ModelQueryBuilder<TRelatedModel>
     */
    protected function getQueryForIntermediateTable(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        $intermediateTable = $this->intermediate::table();
        $relatedNaming = $this->related::naming();

        $intermediateForeign = $intermediateTable . '.' . $relatedNaming->foreignKey();
        $relatedKey = $relatedNaming->table() . '.' . $relatedNaming->key();

        return tap(
            $builder,
            fn(ModelQueryBuilder $builder) =>
            $builder->builder->join($intermediateTable, $intermediateForeign, '=', $relatedKey)
        );
    }

    /**
     * Applies the constraints for a join with the parent table.
     *
     * @param ModelQueryBuilder<TRelatedModel>      $builder
     *
     * @return ModelQueryBuilder<TRelatedModel>
     */
    protected abstract function getQueryForParentTable(ModelQueryBuilder $builder): ModelQueryBuilder;
}

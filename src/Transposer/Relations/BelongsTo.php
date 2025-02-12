<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Model;
use Brickhouse\Support\Collection;

/**
 * @template TModel of Model
 *
 * @extends Relation<TModel>
 */
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class BelongsTo extends Relation
{
    /**
     * @param class-string<TModel>  $model
     * @param null|string|null      $foreignColumn
     * @param null|string|null      $keyColumn
     */
    public function __construct(
        string $model,
        public readonly null|string $foreignColumn = null,
        public readonly null|string $keyColumn = null
    ) {
        parent::__construct($model);
    }

    /**
     * @inheritdoc
     */
    public function match(Collection $rows): Collection
    {
        $keyColumn = $this->parent::key();

        $keys = [];
        foreach ($rows as $row) {
            $keys[] = $row[$keyColumn];
        }

        $models = $this->query
            ->whereIn($keyColumn, $keys)
            ->all()
            ->groupBy($keyColumn);

        foreach ($rows->keys() as $idx) {
            $row = $rows[$idx];

            $modelId = $row[$keyColumn];
            $modelsForRow = $models[$modelId];

            $row[$this->property] = $modelsForRow[0];
            $rows[$idx] = $row;
        }

        return $rows;
    }

    /**
     * @inheritdoc
     */
    public function guessMatchingRelation(string|Model $model): string
    {
        if ($model instanceof Model) {
            $model = $model::class;
        }

        foreach (new $this->model()->getModelRelations() as $property => $relation) {
            if (!$relation instanceof HasOneOrMany) {
                continue;
            }

            if ($relation->model !== $model) {
                continue;
            }

            return $property;
        }

        throw new \RuntimeException("Failed to determine matching HasOneOrMany relation on " . $model);
    }
}

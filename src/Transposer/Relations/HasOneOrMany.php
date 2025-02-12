<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Model;
use Brickhouse\Support\Collection;

/**
 * @template TModel of Model
 *
 * @extends Relation<TModel>
 */
abstract class HasOneOrMany extends Relation
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
        $foreignColumn = $this->parent::naming()->foreignKey();
        $keyColumn = $this->model::key();

        $keys = [];
        foreach ($rows as $row) {
            $keys[] = $row[$keyColumn];
        }

        $models = $this->query
            ->whereIn($foreignColumn, $keys)
            ->all()
            ->groupBy($foreignColumn);

        foreach ($rows->keys() as $idx) {
            $row = $rows[$idx];
            $row[$this->property] = $models[$row[$keyColumn]];

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
            if (!$relation instanceof BelongsTo) {
                continue;
            }

            if ($relation->model !== $model) {
                continue;
            }

            return $property;
        }

        throw new \RuntimeException("Failed to determine matching BelongsTo relation on " . $model);
    }
}

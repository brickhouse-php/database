<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Exceptions\UnresolvableHasOneException;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Support\Collection;

/**
 * @template TModel of Model
 *
 * @extends HasOneOrMany<TModel>
 */
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class HasOne extends HasOneOrMany
{
    /**
     * @param class-string<TModel>  $model
     * @param null|string           $foreignColumn
     * @param null|string           $keyColumn
     * @param bool                  $destroyDependent
     */
    public function __construct(
        string $model,
        null|string $foreignColumn = null,
        null|string $keyColumn = null,
        public readonly bool $destroyDependent = true
    ) {
        parent::__construct($model, $foreignColumn, $keyColumn);
    }

    /**
     * @inheritdoc
     */
    public function match(Collection $rows): Collection
    {
        // Since this is a has-one relation, we need to "patch" the returned array
        // into a single model.
        $rows = parent::match($rows);

        foreach ($rows->keys() as $idx) {
            $row = $rows[$idx];

            if (count($row[$this->property]) !== 1) {
                throw new UnresolvableHasOneException($this->parent, $this->property, $rows);
            }

            $row[$this->property] = $row[$this->property][0];
            $rows[$idx] = $row;
        }

        return $rows;
    }
}

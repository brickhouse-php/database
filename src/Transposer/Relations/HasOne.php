<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Model;

/**
 * @template TModel of Model
 *
 * @extends HasRelation<TModel>
 */
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class HasOne extends HasRelation
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
}

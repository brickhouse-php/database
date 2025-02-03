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
     * @param null|string|null      $column
     * @param null|string|null      $referencedColumn
     */
    public function __construct(
        string $model,
        public readonly null|string $column = null,
        public readonly null|string $referencedColumn = null
    ) {
        parent::__construct($model);
    }
}

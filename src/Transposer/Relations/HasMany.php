<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Model;

/**
 * @template TModel of Model
 *
 * @extends HasRelation<TModel>
 */
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class HasMany extends HasRelation
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
}

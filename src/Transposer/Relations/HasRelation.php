<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Model;

/**
 * @template TModel of Model
 */
abstract class HasRelation
{
    /**
     * @param class-string<TModel>  $model
     */
    public function __construct(public readonly string $model) {}
}

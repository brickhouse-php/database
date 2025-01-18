<?php

namespace Brickhouse\Database\Relations;

use Brickhouse\Database\Model;

/**
 * @template TModel of Model
 */
abstract class HasRelation
{
    /**
     * @param class-string<TModel>  $model
     */
    public function __construct(public readonly string $model)
    {
    }
}

<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Model;

/**
 * @template TModel of Model
 *
 * @extends HasOneOrMany<TModel>
 */
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class HasMany extends HasOneOrMany
{
    //
}

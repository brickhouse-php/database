<?php

namespace Brickhouse\Database\Transposer\Concerns;

use Brickhouse\Database\Transposer\Model;

/**
 * @template TModel of Model
 */
trait HasRelations
{
    /** @use HasAttributes<TModel> */
    use HasAttributes;

    /** @use HasModelQuery<TModel> */
    use HasModelQuery;
}

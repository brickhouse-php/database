<?php

namespace Brickhouse\Database\Transposer\Exceptions;

use Brickhouse\Database\Transposer\Model;
use Brickhouse\Support\Collection;

class UnresolvableHasOneException extends RelationException
{
    /**
     * @param string                                $model
     * @param string                                $relation
     * @param Collection<int,array<string,mixed>>   $records
     */
    public function __construct(
        string $model,
        string $relation,
        public readonly Collection $records
    ) {
        parent::__construct(
            $model,
            $relation,
            'has-one relation returned ' . $records->count() . ' rows, when exactly 1 row should exist.'
        );
    }
}

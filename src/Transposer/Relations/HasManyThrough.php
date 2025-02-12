<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\ModelQueryBuilder;

/**
 * @template TRelatedModel of Model
 * @template TIntermediateModel of Model
 *
 * @extends HasOneOrManyThrough<TRelatedModel,TIntermediateModel>
 */
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class HasManyThrough extends HasOneOrManyThrough
{
    /**
     * @inheritdoc
     */
    protected function getQueryForParentTable(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        $modelTable = $this->parent::table();
        $intermediateNaming = $this->intermediate::naming();

        $modelKey = $modelTable . '.' . $this->parent::key();
        $intermediateForeign = $intermediateNaming->table() . '.' . $this->parent::naming()->foreignKey();

        return tap(
            $builder,
            fn(ModelQueryBuilder $builder) =>
            $builder->builder->join($modelTable, $modelKey, '=', $intermediateForeign)
        );
    }
}

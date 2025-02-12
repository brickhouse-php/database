<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Exceptions\UnresolvableHasOneException;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\ModelQueryBuilder;
use Brickhouse\Support\Collection;

/**
 * @template TRelatedModel of Model
 * @template TIntermediateModel of Model
 *
 * @extends HasOneOrManyThrough<TRelatedModel,TIntermediateModel>
 */
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class HasOneThrough extends HasOneOrManyThrough
{
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

    /**
     * @inheritdoc
     */
    protected function getQueryForParentTable(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        $modelTable = $this->parent::table();
        $intermediateNaming = $this->intermediate::naming();

        $modelForeign = $modelTable . '.' . $intermediateNaming->foreignKey();
        $intermediateKey = $intermediateNaming->table() . '.' . $intermediateNaming->key();

        var_dump("INNER JOIN {$modelTable} ON {$modelForeign} = {$intermediateKey}");

        return tap(
            $builder,
            fn(ModelQueryBuilder $builder) =>
            $builder->builder->join($modelTable, $modelForeign, '=', $intermediateKey)
        );
    }
}

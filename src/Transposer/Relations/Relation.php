<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Model;

/**
 * @template TModel of Model
 */
abstract class Relation
{
    /**
     * @param class-string<TModel>  $model
     */
    public function __construct(public readonly string $model) {}

    /**
     * Guess the property name which matches this relation on the target model.
     *
     * @param class-string<Model>|Model     $model
     *
     * @return string
     */
    public abstract function guessMatchingRelation(string|Model $model): string;
}

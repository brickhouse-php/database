<?php

namespace Brickhouse\Database\Transposer\Relations;

use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\ModelQueryBuilder;
use Brickhouse\Support\Collection;

/**
 * @template TModel of Model
 */
abstract class Relation
{
    /**
     * Defines the class name of the model this relation is applied to.
     *
     * @var class-string<Model>
     */
    protected string $parent;

    /**
     * Defines the name of the property this relation is applied to.
     *
     * @var string
     */
    protected string $property;

    /**
     * Defines the model query builder for the relation.
     *
     * @var ModelQueryBuilder<TModel>
     */
    protected ModelQueryBuilder $query;

    /**
     * @param class-string<TModel>  $model
     */
    public function __construct(public readonly string $model) {}

    /**
     * Sets the parent model class name of the relation.
     *
     * @param class-string<Model>       $parent
     *
     * @return void
     */
    public function setParent(string $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Sets the name of the property which contains the relation.
     *
     * @param string        $property
     *
     * @return void
     */
    public function setProperty(string $property): void
    {
        $this->property = $property;
    }

    /**
     * Sets the model query builder of the relation.
     *
     * @param ModelQueryBuilder<TModel>     $query
     *
     * @return void
     */
    public function setQuery(ModelQueryBuilder $query): void
    {
        $this->query = $query;
    }

    /**
     * Matches the loaded results into the returned rows.
     *
     * @param Collection<int,array<string,mixed>>   $rows
     *
     * @return Collection<int,array<string,mixed>>
     */
    public abstract function match(Collection $rows): Collection;

    /**
     * Guess the property name which matches this relation on the target model.
     *
     * @param class-string<Model>|Model     $model
     *
     * @return string
     */
    public abstract function guessMatchingRelation(string|Model $model): string;
}

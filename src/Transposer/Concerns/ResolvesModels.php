<?php

namespace Brickhouse\Database\Transposer\Concerns;

use Brickhouse\Database\Transposer\Exceptions;
use Brickhouse\Database\Transposer\ModelQueryBuilder;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\Relations\HasOne;
use Brickhouse\Database\Transposer\Relations\Relation;
use Brickhouse\Reflection\ReflectedProperty;
use Brickhouse\Reflection\ReflectedType;
use Brickhouse\Support\Collection;

/**
 * @template TModel of Model
 *
 * @property-read   class-string<TModel>    $modelClass
 */
trait ResolvesModels
{
    /**
     * Resolve models from the rows in the given collection.
     *
     * @param Collection<int,array<string,mixed>> $rows
     *
     * @return Collection<int,TModel>
     */
    protected function resolveModels(Collection $rows): Collection
    {
        $models = Collection::empty();

        $rows = $this->resolveModelRelations($rows);

        foreach ($rows as $row) {
            $models->push($this->modelClass::new($row));
        }

        return $models;
    }

    /**
     * Resolve a model from the given row.
     *
     * @param array<string,mixed> $row
     *
     * @return TModel
     */
    protected function resolveModel(array $row): Model
    {
        $models = $this->resolveModels(Collection::make([$row]));

        return $models[0];
    }

    /**
     * Resolve all the relations on the given row.
     *
     * @param Collection<int,array<string,mixed>> $rows
     *
     * @return Collection<int,array<string,mixed>>
     */
    protected function resolveModelRelations(Collection $rows): Collection
    {
        if (count($rows) <= 0) {
            return $rows;
        }

        $reflector = new ReflectedType($this->modelClass);

        foreach ($this->relations as $relation) {
            // If the relation contains a '.', it means it's a sub-relation.
            // We don't handle those here, as they don't exist on the model. Instead, they're passed to
            // the sub-query when created.
            if (str_contains($relation, ".")) {
                continue;
            }

            $property = $reflector->getProperty($relation);
            if (!$property) {
                throw new Exceptions\RelationNotFoundException($this->modelClass, $relation);
            }

            if (!$this->isPropertyRelation($property)) {
                throw new Exceptions\InvalidRelationException($this->modelClass, $relation);
            }

            $rows = $this->resolveModelRelation($property, $rows);
        }

        return $rows;
    }

    /**
     * Attempt to resolve the relation on the given property.
     * Depending on the relationship type, this method may return a `Model` or a collection of `Model`s.
     *
     * @param ReflectedProperty                     $property   The property instance to resolve the relation on.
     * @param Collection<int,array<string,mixed>>   $rows       The rows which are being resolved.
     *
     * @return Collection<int,array<string,mixed>>
     *
     * @throws Exceptions\UnsupportedRelationException      Thrown if a relational attribute is given, but is unsupported.
     */
    protected function resolveModelRelation(ReflectedProperty $property, Collection $rows): Collection
    {
        // Resolve an instance of the `Relation` attribute.
        $relation = $this->resolvePropertyRelationAttribute($property);
        $relation->setProperty($property->name);
        $relation->setParent($this->modelClass);

        /** @var ModelQueryBuilder<TModel> $builder */
        $builder = new ModelQueryBuilder($relation->model, $this->connection);
        $this->withSubRelations($builder, $property->name);

        $relation->setQuery($builder);

        return $relation->match($rows);
    }

    /**
     * Attempts to get a `Relation` attribute from the given property.
     * If one isn't defined, but the property has a valid `Model` type, an appropriate `HasOne` relation is returned instead.
     *
     * @param ReflectedProperty $property   The property to retrieve the relation from.
     *
     * @return Relation<Model>
     *
     * @throws Exceptions\InvalidRelationAttributeException Thrown if no attribute is found and no valid type is given.
     * @throws Exceptions\InvalidRelationTypeException      Thrown if no attribute is found and the type isn't implementing the `Model`-class.
     */
    protected function resolvePropertyRelationAttribute(ReflectedProperty $property): Relation
    {
        $relationAttribute = $property->attribute(Relation::class, inherit: true);

        if (!$relationAttribute) {
            $relationType = $property->type();

            if (!$relationType || !($relationType instanceof \ReflectionNamedType)) {
                throw new Exceptions\InvalidRelationAttributeException($this->modelClass, $property->name);
            }

            $relationType = new ReflectedType($relationType->getName());
            if (!$relationType->implements(Model::class)) {
                throw new Exceptions\InvalidRelationTypeException($this->modelClass, $property->name);
            }

            return new HasOne($relationType->name);
        }

        return $relationAttribute->create();
    }

    /**
     * Attach all the matching sub-relations on the given query builder.
     *
     * @param ModelQueryBuilder<TModel> $builder    The builder to attach the relations on.
     * @param string                    $base       The base for the relations.
     *
     * @return void
     *
     * @see \Brickhouse\Database\Builder\ModelQueryBuilder::getSubRelations() for more information about "base".
     */
    protected function withSubRelations(ModelQueryBuilder $builder, string $base): void
    {
        $subrelations = $this->getSubRelations($base);
        if (!empty($subrelations)) {
            $builder->with($subrelations);
        }
    }

    /**
     * Get all the matching sub-relations with the given base.
     *
     * The "base" is a prefix for all matching sub-relations, which is removed before returning.
     * For example, given relations of `['author.books', 'publisher']` and a base of `'author'`, `['books']` is returned.
     *
     * @param string    $base   The base for the relations.
     *
     * @return array<int,string>
     */
    protected function getSubRelations(string $base): array
    {
        $subrelations = [];

        foreach ($this->relations as $relation) {
            $prefix = "{$base}.";

            if (!str_starts_with($relation, $prefix)) {
                continue;
            }

            $subrelations[] = substr($relation, strlen($prefix));
        }

        return $subrelations;
    }

    /**
     * Determines whether the given property is fit for a relational property.
     * Currently, relational properties a required to be: `public`, non-`readonly`, non-`abstract` and non-`static`.
     *
     * @param ReflectedProperty $property
     *
     * @return boolean
     */
    protected function isPropertyRelation(ReflectedProperty $property): bool
    {
        return $property->public() && !$property->readonly() && !$property->abstract() && !$property->static();
    }
}

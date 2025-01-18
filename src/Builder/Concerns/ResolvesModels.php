<?php

namespace Brickhouse\Database\Builder\Concerns;

use Brickhouse\Database\Builder\ModelQueryBuilder;
use Brickhouse\Database\Exceptions;
use Brickhouse\Database\Model;
use Brickhouse\Database\Relations\HasMany;
use Brickhouse\Database\Relations\HasOne;
use Brickhouse\Database\Relations\HasRelation;
use Brickhouse\Reflection\ReflectedProperty;
use Brickhouse\Reflection\ReflectedType;
use Brickhouse\Support\Collection;

/**
 * @template TModel of Model
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
        // Resolve an instance of the `HasRelation` attribute.
        $relation = $this->resolvePropertyRelationAttribute($property);

        if ($relation instanceof HasOne) {
            return $this->resolveSingleModelRelation($property, $relation, $rows);
        }

        if ($relation instanceof HasMany) {
            return $this->resolveMultipleModelRelation($property, $relation, $rows);
        }

        throw new Exceptions\UnsupportedRelationException($this->modelClass, $property->name, $relation::class);
    }

    /**
     * Attempts to get a `HasRelation` attribute from the given property.
     * If one isn't defined, but the property has a valid `Model` type, an appropriate `HasOne` relation is returned instead.
     *
     * @param ReflectedProperty $property   The property to retrieve the relation from.
     *
     * @return HasRelation<Model>
     *
     * @throws Exceptions\InvalidRelationAttributeException Thrown if no attribute is found and no valid type is given.
     * @throws Exceptions\InvalidRelationTypeException      Thrown if no attribute is found and the type isn't implementing the `Model`-class.
     */
    protected function resolvePropertyRelationAttribute(ReflectedProperty $property): HasRelation
    {
        $relationAttribute = $property->attribute(HasRelation::class, inherit: true);

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
     * Resolve a `HasOne` relation on the given property.
     *
     * @param ReflectedProperty                     $property
     * @param HasOne<TModel>                        $relation
     * @param Collection<int,array<string,mixed>>   $rows
     *
     * @return Collection<int,array<string,mixed>>
     */
    protected function resolveSingleModelRelation(ReflectedProperty $property, HasOne $relation, Collection $rows): Collection
    {
        $builder = new ModelQueryBuilder($relation->model, $this->connection);
        $this->withSubRelations($builder, $property->name);

        $column = $relation->column ?? "{$property->name}_id";
        $referencedColumn = $relation->referencedColumn ?? "id";

        $keys = [];
        foreach ($rows as $row) {
            $keys[] = $row[$column];
        }

        $models = $builder
            ->whereIn($referencedColumn, $keys)
            ->all()
            ->keyBy($referencedColumn);

        foreach ($rows->keys() as $idx) {
            $row = $rows[$idx];
            $row[$property->name] = $models[$row[$column]];

            $rows[$idx] = $row;
        }

        return $rows;
    }

    /**
     * Resolve a `HasMany` relation on the given property.
     *
     * @param ReflectedProperty                     $property
     * @param HasMany<TModel>                       $relation
     * @param Collection<int,array<string,mixed>>   $rows
     *
     * @return Collection<int,array<string,mixed>>
     */
    protected function resolveMultipleModelRelation(ReflectedProperty $property, HasMany $relation, Collection $rows): Collection
    {
        $builder = new ModelQueryBuilder($relation->model, $this->connection);
        $this->withSubRelations($builder, $property->name);

        $foreignColumn = $relation->foreignColumn ?? $this->modelClass::naming()->foreignKey();
        $keyColumn = $relation->keyColumn ?? "id";

        $keys = [];
        foreach ($rows as $row) {
            $keys[] = $row[$keyColumn];
        }

        $models = $builder
            ->whereIn($foreignColumn, $keys)
            ->all()
            ->groupBy($foreignColumn);

        foreach ($rows->keys() as $idx) {
            $row = $rows[$idx];
            $row[$property->name] = $models[$row[$keyColumn]];

            $rows[$idx] = $row;
        }

        return $rows;
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

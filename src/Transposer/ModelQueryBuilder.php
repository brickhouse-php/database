<?php

namespace Brickhouse\Database\Transposer;

use Brickhouse\Database\Builder\QueryBuilder;
use Brickhouse\Database\DatabaseConnection;
use Brickhouse\Database\Transposer\Concerns;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\Relations\BelongsTo;
use Brickhouse\Database\Transposer\Relations\HasOne;
use Brickhouse\Database\Transposer\Relations\HasRelation;
use Brickhouse\Support\Collection;

/**
 * @template TModel of Model
 */
class ModelQueryBuilder
{
    /** @use Concerns\ResolvesModels<TModel> */
    use Concerns\ResolvesModels;

    /**
     * Gets the underlying `QueryBuilder`-instance for executing the queries.
     *
     * @var QueryBuilder
     */
    public readonly QueryBuilder $builder;

    /**
     * Array of relations to load into the model.
     *
     * @var array<int,string>
     */
    protected array $relations = [];

    /**
     * @param class-string<TModel>  $modelClass     Defines the class name of the model for the builder.
     * @param DatabaseConnection    $connection     Defines the connection the builder is operating on.
     */
    public function __construct(
        public readonly string $modelClass,
        public readonly DatabaseConnection $connection,
    ) {
        $this->builder = new QueryBuilder($this->connection)
            ->from($this->modelClass::table());
    }

    /**
     * Adds a conditional `WHERE` clause to the query, limiting what rows/records are affected by the query.
     *
     * @param string    $column             Name of the column to apply the condition on.
     * @param mixed     $operatorOrValue    Either the operator for the clause, or the value the column should equal to.
     * @param mixed     $value              If `$operatorOrValue` is an operator, defines the value the column should equal to.
     *
     * @return self<TModel>
     */
    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        $this->builder->where($column, $operatorOrValue, $value);
        return $this;
    }

    /**
     * Adds a conditional `WHERE` clause to the query, limiting what rows/records are affected by the query.
     *
     * @param string            $column     Name of the column to apply the condition on.
     * @param array<int,mixed>  $values     Defines the value the column should equal to.
     *
     * @return self<TModel>
     */
    public function whereIn(string $column, array $values): self
    {
        $this->builder->whereIn($column, $values);
        return $this;
    }

    /**
     * Limits the amount of rows to return to, at most, `$amount` rows.
     *
     * @param int   $amount     The maximum amount of rows to return.
     *
     * @return self<TModel>
     */
    public function take(int $amount): self
    {
        $this->builder->take($amount);
        return $this;
    }

    /**
     * Loads additional relation(s) on the model, when queried.
     *
     * @param   list<string>|string     $relations
     *
     * @return self<TModel>
     */
    public function with(array|string ...$relations): self
    {
        $relations = array_flatten($relations);
        $relations = array_wrap($relations);

        foreach ($relations as $relation) {
            if (in_array($relation, $this->relations)) {
                continue;
            }

            $this->relations[] = $relation;
        }

        return $this;
    }

    /**
     * Gets all instances of the model from the database.
     *
     * @return Collection<int,TModel>
     */
    public function all(): Collection
    {
        /** @var Collection<int,array<string,mixed>> $results */
        $results = $this->builder->get();

        return $this->resolveModels($results);
    }

    /**
     * Finds the first model with the given ID, if it exists. Otherwise, returns `null`.
     *
     * @param   string|int  $id     The ID to query for.
     *
     * @return null|TModel
     */
    public function find(string|int $id): ?Model
    {
        $result = $this->builder
            ->where($this->modelClass::key(), '=', $id)
            ->first();

        if ($result === null) {
            return null;
        }

        return $this->resolveModel($result);
    }

    /**
     * Executes the query and get the first result. If no results are returned, returns `null`.
     *
     * @param null|string|list<string>  $columns    Defines which columns to select. If not `null`, overwrites previously selected columns.
     *
     * @return null|TModel
     */
    public function first(null|string|array $columns = null): ?Model
    {
        $result = $this->builder->first($columns);
        if ($result === null) {
            return null;
        }

        return $this->resolveModel($result);
    }

    /**
     * Saves the given model to the database.
     *
     * @param TModel $model
     *
     * @return TModel
     */
    public function insert(Model $model): Model
    {
        if ($model->modelState === Model::STATE_PERSISTING) {
            return $model;
        }

        $this->finalizeRelationsBeforeSave($model);

        [$result] = $this->builder->insert(
            $model->getInsertableAttributes()
        );

        $model->id = $result[$model::key()];

        return tap($model->refresh(), function (Model $model) {
            $model->modelState = Model::STATE_PERSISTED;

            $this->finalizeRelationsAfterSave($model);
        });
    }

    /**
     * Saves the given models to the database.
     *
     * @param array<int,TModel>|Collection<int,TModel>  $models
     *
     * @return Collection<int,TModel>
     */
    public function insertMany(array|Collection $models): Collection
    {
        return Collection::wrap($models)->map($this->insert(...));
    }

    /**
     * Updates the given model to the database.
     *
     * @param TModel $model
     *
     * @return TModel
     */
    public function update(Model $model): Model
    {
        if ($model->modelState === Model::STATE_PERSISTING) {
            return $model;
        }

        $this->finalizeRelationsBeforeSave($model);

        $this->destroyDependentRelations($model);

        // Since we just updated all relations, we retrieve only the dirty attributes which
        // are non-relational.
        $attributesToUpdate = $model->getDirtyAttributes(include_relations: false);

        $this->builder
            ->where('id', $model->id)
            ->update($attributesToUpdate);

        $this->finalizeRelationsBeforeSave($model);

        return tap($model->refresh(), function (Model $model) {
            $model->modelState = Model::STATE_PERSISTED;

            $this->finalizeRelationsAfterSave($model);
        });
    }

    /**
     * Updates the given models in the database.
     *
     * @param array<int,TModel>|Collection<int,TModel>  $models
     *
     * @return Collection<int,TModel>
     */
    public function updateMany(array|Collection $models): Collection
    {
        return Collection::wrap($models)->map($this->update(...));
    }

    /**
     * Insert the given model in the database, if it doesn't exist. Otherwise, updates the existing record.
     *
     * @param Model     $model
     *
     * @return Model
     */
    public function upsert(Model $model): Model
    {
        return $model->exists
            ? $this->update($model)
            : $this->insert($model);
    }

    /**
     * Updates the given models in the database.
     *
     * @param array<int,TModel>|Collection<int,TModel>  $models
     *
     * @return Collection<int,TModel>
     */
    public function upsertMany(array|Collection $models): Collection
    {
        $models = Collection::wrap($models)->groupBy(
            fn(Model $model) => $model->exists ? 1 : 0
        );

        $newModels = [];

        if (isset($models[0])) {
            $newModels += $this->insertMany($models[0])->toArray();
        }

        if (isset($models[1])) {
            $newModels += $this->updateMany($models[1])->toArray();
        }

        return Collection::wrap($newModels);
    }

    /**
     * Deletes the given model from the database.
     *
     * @param TModel $model
     *
     * @return TModel
     */
    public function delete(Model $model): Model
    {
        $deleted = $this->builder
            ->where('id', $model->id)
            ->delete();

        if ($deleted) {
            // Set the ID as null, since the model no longer exists in the database.
            $model->id = null;
        }

        return $model;
    }

    /**
     * Deletes all has-one relations, which are outdated and marked with `$destroyDependent = true`. This
     * attempts to delete previous relations when a has-one relation is updated to prevent multiple resolved models.
     *
     * @param Model     $model
     *
     * @return void
     */
    protected function destroyDependentRelations(Model $model): void
    {
        if ($model->modelState === Model::STATE_PERSISTING) {
            return;
        }

        $model->modelState = Model::STATE_PERSISTING;

        $originalAttributes = $model->getOriginalAttributes();

        foreach ($model->getModelRelations() as $property => $relation) {
            if (!isset($model->$property)) {
                continue;
            }

            // If the relation is not a has-one relation or dependent destroy is not enabled, skip over it.
            if (!$relation instanceof HasOne || !$relation->destroyDependent) {
                continue;
            }

            // If the attribute hasn't actually updated, we have nothing to delete.
            if (!$model->isAttributeDirty($property) || !isset($originalAttributes[$property])) {
                continue;
            }

            if (!($original = $originalAttributes[$property]) instanceof Model) {
                throw new \RuntimeException(
                    'Found non-Model instance in original attributes (' . $original::class . ')'
                );
            }

            $original->delete();
        }

        $model->modelState = Model::STATE_PERSISTED;
    }

    /**
     * Finalizes and/or saves all relations on the given model when it's being persisted to the database.
     *
     * @param Model     $model
     *
     * @return void
     */
    protected function finalizeRelationsBeforeSave(Model $model): void
    {
        if ($model->modelState === Model::STATE_PERSISTING) {
            return;
        }

        $model->modelState = Model::STATE_PERSISTING;

        foreach ($model->getModelRelations() as $property => $relation) {
            if (!isset($model->$property)) {
                continue;
            }

            if (!$relation instanceof BelongsTo) {
                continue;
            }

            $modelQueryBuilder = new ModelQueryBuilder($relation->model, $this->connection);
            $modelQueryBuilder->upsertMany(Collection::wrap($model->$property));
        }

        $model->modelState = Model::STATE_PERSISTED;
    }

    /**
     * Finalizes and/or saves all relations on the given model when it's being persisted to the database.
     *
     * @param Model     $model
     *
     * @return void
     */
    protected function finalizeRelationsAfterSave(Model $model): void
    {
        if ($model->modelState === Model::STATE_PERSISTING) {
            return;
        }

        $model->modelState = Model::STATE_PERSISTING;

        foreach ($model->getModelRelations() as $property => $relation) {
            if (!isset($model->$property)) {
                continue;
            }

            if (!$relation instanceof HasRelation) {
                continue;
            }

            $modelQueryBuilder = new ModelQueryBuilder($relation->model, $this->connection);
            $modelQueryBuilder->upsertMany(Collection::wrap($model->$property));
        }

        $model->modelState = Model::STATE_PERSISTED;
    }
}

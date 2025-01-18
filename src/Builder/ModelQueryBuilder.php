<?php

namespace Brickhouse\Database\Builder;

use Brickhouse\Database\Builder\Concerns;
use Brickhouse\Database\DatabaseConnection;
use Brickhouse\Database\Model;
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
    protected readonly QueryBuilder $builder;

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
    public function where(string $column, $operatorOrValue, $value = null): self
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
    public function find(string|int $id)
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
    public function first(null|string|array $columns = null)
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
        $values = [];

        foreach ($model->getProperties() as $key => $value) {
            // Let the database create the key for us.
            if ($key === $model::key()) {
                continue;
            }

            $values[$key] = $value;
        }

        $model->id = $this->builder->insert($values)[0]['id'];

        return $model->refresh();
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
        $values = [];

        foreach ($model->getProperties() as $key => $value) {
            // We should never update the key, as it might have some severe consequences.
            if ($key === $model::key()) {
                continue;
            }

            $values[$key] = $value;
        }

        $this->builder
            ->where('id', $model->id)
            ->update($values);

        return $model->refresh();
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
}

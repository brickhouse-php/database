<?php

namespace Brickhouse\Database\Transposer\Concerns;

use Brickhouse\Database\Builder\QueryBuilder;
use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\ModelQueryBuilder;
use Brickhouse\Support\Collection;

/**
 * @template TModel of Model
 */
trait HasModelQuery
{
    /** @use HasNamingStrategy<TModel> */
    use HasNamingStrategy;

    /**
     * Gets the name of the database connection the model is defined on.
     */
    public static function connection(): null|string
    {
        return null;
    }

    /**
     * Creates a query builder for the current model.
     *
     * @return ModelQueryBuilder<static>
     */
    public static function query(): ModelQueryBuilder
    {
        $connectionManager = resolve(ConnectionManager::class);
        $connection = $connectionManager->connection(static::connection());

        return new ModelQueryBuilder(static::class, $connection);
    }

    /**
     * Finds the first model with the given ID, if it exists. Otherwise, returns `null`.
     *
     * @param   string|int  $id     The ID to query for.
     *
     * @return null|static
     */
    public static function find(string|int $id): null|static
    {
        return static::query()->find($id);
    }

    /**
     * Gets all the current models in the database.
     *
     * @return Collection<int,static>
     */
    public static function all(): Collection
    {
        return static::query()->all();
    }

    /**
     * Creates a new query builder which will load all the given relations.
     *
     * @param array<int,string>|string      $relations
     *
     * @return ModelQueryBuilder<static>
     */
    public static function with(array|string ...$relations): ModelQueryBuilder
    {
        return static::query()->with(...$relations);
    }

    /**
     * Finds the first model in the database which match the given attributes.
     *
     * @param array<string,mixed>       $attributes
     *
     * @return null|static
     */
    public static function findBy(array $attributes): null|static
    {
        $query = static::query();

        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }

    /**
     * Deletes all the model in the database which match the given attributes.
     *
     * @param array<string,mixed>       $attributes
     *
     * @return void
     */
    public static function deleteBy(array $attributes): void
    {
        $query = new QueryBuilder(self::query()->connection)->from(self::table());

        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }

        $query->delete();
    }
}

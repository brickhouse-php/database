<?php

namespace Brickhouse\Database;

use Brickhouse\Database\Builder\ModelQueryBuilder;
use Brickhouse\Database\Relations\HasRelation;
use Brickhouse\Support\Collection;

interface Model
{
    /**
     * Gets the ID of the model.
     *
     * @var null|int|string
     */
    public $id { get; set; }

    /**
     * Gets whether the model exists in the database.
     *
     * @var bool
     */
    public bool $exists { get; }

    /**
     * Gets the name of the primary key column.
     */
    public static function key(): string;

    /**
     * Gets the name of the database connection the model is defined on.
     */
    public static function connection(): null|string;

    /**
     * Gets the name of the table to store the model in.
     */
    public static function table(): string;

    /**
     * Gets the naming strategy for the model.
     *
     * @return NamingStrategy<self>
     */
    public static function naming(): NamingStrategy;

    /**
     * Creates a query builder for the current model.
     *
     * @return ModelQueryBuilder<self>
     */
    public static function query(): ModelQueryBuilder;

    /**
     * Finds the first model with the given ID, if it exists. Otherwise, returns `null`.
     *
     * @param   string|int  $id     The ID to query for.
     *
     * @return null|self
     */
    public static function find(string|int $id): null|self;

    /**
     * Gets all the current models in the database.
     *
     * @return Collection<int,self>
     */
    public static function all(): Collection;

    /**
     * Creates a new instance of the model without saving it to the database.
     * Optionally, fills the given properties into the model.
     *
     * @param array<string,mixed>   $properties     Optional properties to fill into the model on creation.
     *
     * @return self
     */
    public static function new(array $properties = []): self;

    /**
     * Creates a new instance of the model and saves it to the database.
     * Optionally, fills the given properties into the model.
     *
     * @param array<string,mixed>   $properties     Optional properties to fill into the model on creation.
     *
     * @return self
     */
    public static function create(array $properties = []): self;

    /**
     * Fills the model with the given properties.
     *
     * @param array<string,mixed>   $properties
     *
     * @return self
     */
    public function fill(array $properties): self;

    /**
     * Saves the model to the database and returns the model with it's updated database values.
     *
     * @param array<string,mixed>   $properties
     *
     * @return self
     */
    public function save(array $properties = []): self;

    /**
     * Refreshes the model with updated values from the database and returns it.
     *
     * @return self
     */
    public function refresh(): self;

    /**
     * Gets all the default values for the properties.
     *
     * @return array<string,mixed>
     */
    public static function defaults(): array;

    /**
     * Gets the names of all mappable properties on the model.
     *
     * @return array<int,string>
     */
    public static function mappable(): array;

    /**
     * Gets all the relations defined on the model.
     *
     * @return array<string,HasRelation<static>>
     */
    public static function relations(): array;

    /**
     * Gets all the property values defined on the model.
     *
     * @return array<string,mixed>
     */
    public function getProperties(): array;
}

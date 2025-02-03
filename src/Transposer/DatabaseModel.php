<?php

namespace Brickhouse\Database\Transposer;

use Brickhouse\Database\ConnectionManager;
use Brickhouse\Database\Transposer\ModelQueryBuilder;
use Brickhouse\Database\Transposer\Naming\SnakeCaseNamingStrategy;
use Brickhouse\Database\Transposer\Relations\HasRelation;
use Brickhouse\Reflection\ReflectedType;
use Brickhouse\Support\Collection;

/**
 * @phpstan-require-implements \Brickhouse\Database\Transposer\Model
 */
trait DatabaseModel
{
    /**
     * Gets the ID of the model.
     *
     * @var null|int|string
     */
    public $id = null;

    /**
     * Gets whether the model exists in the database.
     *
     * @var bool
     */
    public bool $exists {
        get => $this->id !== null;
    }

    /**
     * Contains an instance of the default naming strategy.
     *
     * @var null|NamingStrategy
     */
    private static null|NamingStrategy $defaultNamingStrategy;

    /**
     * @inheritDoc
     */
    public static function key(): string
    {
        return self::naming()->key();
    }

    /**
     * @inheritDoc
     */
    public static function connection(): null|string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public static function table(): string
    {
        return self::naming()->table();
    }

    /**
     * @inheritDoc
     */
    public static function naming(): NamingStrategy
    {
        if (($value = new ReflectedType(self::class)->getStaticPropertyValue('naming')) !== null) {
            return $value;
        }

        return (self::$defaultNamingStrategy ?? new SnakeCaseNamingStrategy(self::class));
    }

    /**
     * @inheritDoc
     */
    public static function query(): ModelQueryBuilder
    {
        $connectionManager = resolve(ConnectionManager::class);
        $connection = $connectionManager->connection(self::connection());

        return new ModelQueryBuilder(self::class, $connection);
    }

    /**
     * @inheritDoc
     */
    public static function find(string|int $id): null|self
    {
        return self::query()->find($id);
    }

    /**
     * @inheritDoc
     */
    public static function all(): Collection
    {
        return self::query()->all();
    }

    /**
     * @inheritDoc
     */
    public static function new(array $properties = []): static
    {
        /** @var self $model */
        $model = resolve(ModelBuilder::class)
            ->create(self::class, $properties);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public static function create(array $properties = []): static
    {
        return self::new($properties)->save();
    }

    /**
     * @inheritDoc
     */
    public function inspect(): void
    {
        throw new \Exception("Not implemented.");
    }

    /**
     * @inheritDoc
     */
    public function fill(array $properties): static
    {
        $mappable = self::mappableAttributes();
        $relations = self::modelRelations();

        foreach ($mappable as $property) {
            if (! array_key_exists($property, $properties)) {
                continue;
            }

            if (in_array($property, array_keys($relations))) {
                continue;
            }

            $this->$property = $properties[$property];
        }

        foreach ($properties as $key => $value) {
            if (array_key_exists($key, $properties)) {
                continue;
            }

            $this->$key = $value;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function save(array $attributes = []): static
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }

        $query = $this->query();

        if ($this->exists) {
            $model = $query->update($this);
        } else {
            $model = $query->insert($this);
        }

        /** @var self $model */
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function delete(): static
    {
        $model = $this->query()->delete($this);

        /** @var self $model */
        return $model;
    }

    /**
     * @inheritDoc
     */
    public function refresh(): static
    {
        if (!$this->exists) {
            throw new \RuntimeException("Attempted to update model which doesn't exist (" . self::class . ")");
        }

        $updated = $this->query()->find($this->id);
        if (!$updated) {
            throw new \RuntimeException("Could not update model: database entry does not exist (" . self::class . ")");
        }

        $this->fill($updated->getProperties());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getProperties(): array
    {
        $values = [];

        $properties = self::mappableAttributes();
        $relations = self::modelRelations();

        foreach ($properties as $property) {
            if (!isset($this->$property)) {
                continue;
            }

            $value = $this->$property;

            // Don't include relations in the property value array.
            if (array_key_exists($property, $relations)) {
                continue;
            }

            $values[$property] = $value;
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public static function attributeDefaults(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function mappableAttributes(): array
    {
        $properties = [self::key()];

        foreach (new ReflectedType(self::class)->getProperties() as $property) {
            if (!$property->public() || $property->readonly() || $property->abstract() || $property->static() || $property->hooked()) {
                continue;
            }

            $properties[] = $property->name;
        }

        return $properties;
    }

    /**
     * @inheritDoc
     */
    public static function modelRelations(): array
    {
        $relations = [];

        foreach (new ReflectedType(self::class)->getProperties() as $property) {
            if (!$property->public() || $property->readonly() || $property->abstract() || $property->static()) {
                continue;
            }

            $relation = $property->attribute(HasRelation::class, inherit: true);
            if (!$relation) {
                continue;
            }

            $relations[$property->name] = $relation->create();
        }

        return $relations;
    }
}

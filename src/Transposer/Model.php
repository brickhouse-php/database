<?php

namespace Brickhouse\Database\Transposer;

use Brickhouse\Database\Transposer\Concerns;
use Brickhouse\Database\Transposer\Exceptions\UnloadedRelationException;

abstract class Model implements \JsonSerializable
{
    /** @use Concerns\HasAttributes<self> */
    use Concerns\HasAttributes;

    /** @use Concerns\HasModelQuery<self> */
    use Concerns\HasModelQuery;

    /** @use Concerns\HasNamingStrategy<self> */
    use Concerns\HasNamingStrategy;

    /** @use Concerns\HasRelations<self> */
    use Concerns\HasRelations;

    public const int STATE_NEW = 0;
    public const int STATE_PERSISTING = 1;
    public const int STATE_PERSISTED = 2;

    /**
     * Gets the ID of the model.
     *
     * @var null|int|string
     */
    public null|int|string $id = null;

    /**
     * Gets whether the model exists in the database.
     *
     * @var bool
     */
    public bool $exists {
        get => $this->id !== null;
    }

    /**
     * Gets the current state of the model.
     *
     * @var int
     */
    #[Ignore]
    public int $modelState = self::STATE_NEW;

    public final function __construct() {}

    /**
     * Creates a new instance of the model without saving it to the database.
     * Optionally, fills the given properties into the model.
     *
     * @param array<string,mixed>   $properties     Optional properties to fill into the model on creation.
     *
     * @return static
     */
    public static function new(array $properties = []): static
    {
        /** @var static $model */
        $model = resolve(ModelBuilder::class)
            ->create(static::class, $properties);

        return $model;
    }

    /**
     * Creates a new instance of the model and saves it to the database.
     * Optionally, fills the given properties into the model.
     *
     * @param array<string,mixed>   $properties     Optional properties to fill into the model on creation.
     *
     * @return static
     */
    public static function create(array $properties = []): static
    {
        return static::new($properties)->save();
    }

    /**
     * Returns a string-representation of the model.
     *
     * @return string
     */
    public function inspect(): string
    {
        return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
    }

    /**
     * Saves the model to the database and returns the model with it's updated database values.
     *
     * @return static
     */
    public function save(): static
    {
        // If the model hasn't been altered since it was retrieved from the database,
        // we'll cut the method short and return straight away.
        if (!$this->isDirty()) {
            return $this;
        }

        return new ChangeTracker()->save($this);
    }

    /**
     * Refreshes the model with updated values from the database and returns it.
     *
     * @return static
     */
    public function refresh(): static
    {
        if (!$this->exists) {
            throw new \RuntimeException("Attempted to update model which doesn't exist (" . static::class . ")");
        }

        $updated = $this->query()->find($this->id);
        if (!$updated) {
            throw new \RuntimeException("Could not update model: database entry does not exist (" . static::class . ")");
        }

        $this->setOriginalAttributes(
            $updated->getProperties(include_relations: true)
        );

        $this->fill($updated->getProperties());

        return $this;
    }

    /**
     * Deletes the model from the database.
     *
     * @return self
     */
    public function delete(): self
    {
        return $this->query()->delete($this);
    }

    public function jsonSerialize(): mixed
    {
        $properties = $this->getProperties(include_relations: true);

        foreach ($properties as $idx => $property) {
            // Serialize nested models as well
            if ($property instanceof Model) {
                $properties[$idx] = $property->jsonSerialize();
            }
        }

        return $properties;
    }

    /**
     * Dynamically retrieves properties from the model, which don't match any other property.
     */
    public function __get(string $key): mixed
    {
        // If the caller is attempting to retrieve a relational attribute, but it is uninitialized,
        // it is likely because they haven't loaded the relation yet.
        if ($this->isModelRelation($key)) {
            throw new UnloadedRelationException($this::class, $key);
        }

        // If the property doesn't exist in the model, it might be attempting to reference a column
        // value which was retrieved when querying the model. This is mostly for retrieving IDs in relations,
        // as they wouldn't have their own property on the model.
        $auxiliaryAttributes = $this->getAuxiliaryAttributes();
        if (isset($auxiliaryAttributes[$key])) {
            return $auxiliaryAttributes[$key];
        }

        throw new \RuntimeException('Invalid model property: ' . $this::class . '::' . $key);
    }
}

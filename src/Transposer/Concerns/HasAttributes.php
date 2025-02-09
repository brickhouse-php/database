<?php

namespace Brickhouse\Database\Transposer\Concerns;

use Brickhouse\Database\Transposer\Ignore;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\Relations\BelongsTo;
use Brickhouse\Database\Transposer\Relations\Relation;
use Brickhouse\Reflection\ReflectedProperty;
use Brickhouse\Reflection\ReflectedType;

/**
 * @template TModel of Model
 *
 * @phpstan-require-extends Model
 */
trait HasAttributes
{
    /** @use HasNamingStrategy<TModel> */
    use HasNamingStrategy;

    /**
     * Contains the mappable attributes of the model.
     *
     * @var array<int,string>
     */
    private array $mappableAttributes;

    /**
     * Contains the original state of the model attributes.
     *
     * @var array<string,mixed>
     */
    private array $originalAttributes = [];

    /**
     * Contains database columns which don't map to any specific model attribute.
     *
     * @var array<string,mixed>
     */
    private array $auxiliaryAttributes = [];

    /**
     * Contains the names of all relational attributes on the model.
     *
     * @var array<string,Relation<*>>
     */
    private array $relationalAttributes;

    /**
     * Fills the model with the given properties.
     *
     * @param array<string,mixed>   $properties
     *
     * @return self
     */
    public function fill(array $properties): self
    {
        foreach ($this->getMappableAttributes() as $property) {
            if (!array_key_exists($property, $properties)) {
                continue;
            }

            if ($this->isModelRelation($property)) {
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
     * Gets all the property values defined on the model.
     *
     * @param bool  $include_relations      Whether to include relations in the returned array. Defaults to `false`.
     *
     * @return array<string,mixed>
     */
    public function getProperties(bool $include_relations = false): array
    {
        $values = [];

        foreach ($this->getMappableAttributes() as $property) {
            if (!isset($this->$property)) {
                continue;
            }

            $value = $this->$property;

            // Don't include relations in the property value array, if requested.
            if (!$include_relations && $this->isModelRelation($property)) {
                continue;
            }

            $values[$property] = $value;
        }

        return $values;
    }

    /**
     * Gets all the attributes on the model which are valid for database insertion.
     *
     * @return array<string,mixed>
     */
    public function getInsertableAttributes(): array
    {
        $attributes = array_filter(
            $this->getProperties(include_relations: false),
            fn(string $key) => $key !== self::key(),
            ARRAY_FILTER_USE_KEY
        );

        foreach ($this->getModelRelations() as $property => $relation) {
            if (!isset($this->$property)) {
                continue;
            }

            if (!$relation instanceof BelongsTo) {
                continue;
            }

            $columnName = $relation->model::naming()->foreignKey();
            $attributes[$columnName] = $this->$property->id;
        }

        return $attributes;
    }

    /**
     * Gets the original state of the model from when it was created / queried.
     *
     * @return array<string,mixed>
     */
    public function getOriginalAttributes(): array
    {
        return $this->originalAttributes;
    }

    /**
     * Sets the original state of the model from when it was last saved.
     *
     * @param array<string,mixed>       $attributes
     *
     * @return void
     */
    public function setOriginalAttributes(array $attributes): void
    {
        $this->originalAttributes = $attributes;
    }

    /**
     * Gets the auxiliary attributes of the model.
     *
     * @return array<string,mixed>
     */
    public function getAuxiliaryAttributes(): array
    {
        return $this->auxiliaryAttributes;
    }

    /**
     * Adds an auxiliary attribute to the model.
     *
     * @param string        $key
     * @param mixed         $value
     *
     * @return void
     */
    public function addAuxiliaryAttribute(string $key, mixed $value): void
    {
        $this->auxiliaryAttributes[$key] = $value;
    }

    /**
     * Gets all the attributes on the model which have been changed from it's original state.
     *
     * @param bool  $include_relations  Whether to include relations in the returned array. Defaults to `false`.
     *
     * @return array<string,mixed>
     */
    public function getDirtyAttributes(bool $include_relations = true): array
    {
        return array_filter(
            $this->getProperties($include_relations),
            $this->isAttributeDirty(...),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Determines whether the attribute with the given key is dirty.
     *
     * @return bool
     */
    public function isAttributeDirty(string $key): bool
    {
        // If the attribute isn't set, ignore.
        if (!array_key_exists($key, $this->originalAttributes) || !isset($this->$key)) {
            return true;
        }

        $attribute = $this->$key;
        $original = $this->originalAttributes[$key];

        return $attribute !== $original;
    }

    /**
     * Determines whether model has any dirty attributes.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        if (!$this->exists) {
            return true;
        }

        return !empty($this->getDirtyAttributes(include_relations: true));
    }

    /**
     * Gets the names of all mappable properties on the model.
     *
     * @return array<int,string>
     */
    public function getMappableAttributes(): array
    {
        if (isset($this->mappableAttributes)) {
            return $this->mappableAttributes;
        }

        $properties = [self::key()];

        foreach (new ReflectedType(static::class)->getProperties() as $prop) {
            if (!$prop->public() || $prop->readonly() || $prop->abstract() || $prop->static() || $prop->hooked()) {
                continue;
            }

            if ($prop->attribute(Ignore::class) !== null) {
                continue;
            }

            if (in_array($prop->name, $properties)) {
                continue;
            }

            $properties[] = $prop->name;
        }

        $this->mappableAttributes = $properties;

        return $properties;
    }

    /**
     * Gets all mappable properties on the model.
     *
     * @return array<string,ReflectedProperty>
     */
    public function getMappableAttributeProperties(): array
    {
        if (!isset($this->mappableAttributes)) {
            $this->getMappableAttributes();
        }

        $properties = [];
        $reflector = new ReflectedType($this::class);

        foreach ($this->getMappableAttributes() as $propertyName) {
            $property = $reflector->getProperty($propertyName);

            if ($property !== null) {
                $properties[$propertyName] = $property;
            }
        }

        return $properties;
    }

    /**
     * Gets all the relations defined on the model.
     *
     * @return array<string,Relation<*>>
     */
    public function getModelRelations(): array
    {
        if (isset($this->relationalAttributes)) {
            return $this->relationalAttributes;
        }

        $relations = [];

        foreach ($this->getMappableAttributeProperties() as $property) {
            $relation = $property->attribute(Relation::class, inherit: true);
            if (!$relation) {
                continue;
            }

            $relations[$property->name] = $relation->create();
        }

        $this->relationalAttributes = $relations;

        return $relations;
    }

    /**
     * Determines whether the attribute with the given key is a relation.
     *
     * @param string    $key
     *
     * @return bool
     */
    public function isModelRelation(string $key): bool
    {
        if (!isset($this->relationalAttributes)) {
            $this->getModelRelations();
        }

        return isset($this->relationalAttributes[$key]);
    }

    /**
     * Gets the model relation for the attribute with the given key. If the attribute is not a relation, returns `null`.
     *
     * @param string    $key
     *
     * @return ?Relation<*>
     */
    public function getModelRelation(string $key): ?Relation
    {
        if (!isset($this->relationalAttributes)) {
            $this->getModelRelations();
        }

        return $this->relationalAttributes[$key] ?? null;
    }

    /**
     * Gets all the default values for the properties.
     *
     * @return array<string,mixed>
     */
    public static function attributeDefaults(): array
    {
        return [];
    }
}

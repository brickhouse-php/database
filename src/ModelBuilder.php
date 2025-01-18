<?php

namespace Brickhouse\Database;

use Brickhouse\Database\Relations\HasRelation;
use Brickhouse\Reflection\ReflectedProperty;
use Brickhouse\Reflection\ReflectedType;
use Brickhouse\Support\Collection;

/**
 * @template TModel of Model
 */
class ModelBuilder
{
    /**
     * Contains cached lists of properties on all created models.
     *
     * @var array<class-string<TModel>,array<string,ReflectedProperty>>
     */
    protected array $properties = [];

    /**
     * Contains cached lists of relations on all created models.
     *
     * @var array<class-string<TModel>,array<string,HasRelation<TModel>>>
     */
    protected array $relations = [];

    /**
     * Creates a new instance of the model and fills it with the given properties.
     *
     * @param class-string<TModel>  $model          Name of the model to create an instance of.
     * @param array<string,mixed>   $attributes     Properties to fill into the model on creation.
     *
     * @return TModel
     */
    public function create(string $model, array $attributes)
    {
        try {
            $reflector = new ReflectedType($model);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException("Target model class [{$model}] does not exist.", 0, $e);
        }

        if (!$reflector->instantiable()) {
            throw new \RuntimeException("Target model class [{$model}] cannot be instantiated.");
        }

        $instance = $reflector->newInstanceWithoutConstructor();

        $this->loadModelAttributes($reflector, $instance, $attributes);
        $this->loadRelatedAttributes($reflector, $instance, $attributes);

        return $instance;
    }

    /**
     * Load all the attributes in `$attributes` into the model instance.
     *
     * @param ReflectedType<TModel> $reflector
     * @param TModel                $instance
     * @param array<string,mixed>   $attributes     Properties to fill into the model on creation.
     */
    protected function loadModelAttributes(ReflectedType $reflector, Model $instance, array $attributes): void
    {
        $properties = $this->resolveModelProperties($reflector);
        $relations = $this->resolveModelRelations($reflector->name);
        $defaults = $instance::defaults();

        foreach ($properties as $property) {
            if (!array_key_exists($property->name, $attributes)) {
                // If no value is given from the attributes, attempt to set it from the model defaults.
                if (array_key_exists($property->name, $defaults)) {
                    $property->setValue($instance, $defaults[$property->name]);
                    continue;
                }

                // Unset the property, which forces PHP to call the `__get` method on the model
                // in case of unresolved attributes. Except if it's the ID - that should be `NULL` if not given.
                if ($property->name !== $instance::key()) {
                    unset($instance->{$property->name});
                }

                continue;
            }

            if (in_array($property->name, array_keys($relations))) {
                continue;
            }

            $property->setValue($instance, $attributes[$property->name]);
        }

        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, $properties)) {
                continue;
            }

            $instance->$key = $value;
        }
    }

    /**
     * Load all related attributes from the model into the model instance.
     *
     * @param ReflectedType<TModel> $reflector
     * @param TModel                $instance
     * @param array<string,mixed>   $attributes     Properties to fill into the model on creation.
     */
    protected function loadRelatedAttributes(ReflectedType $reflector, Model $instance, array $attributes): void
    {
        $relations = $this->resolveModelRelations($reflector->name);

        foreach (array_keys($relations) as $propertyName) {
            if (!array_key_exists($propertyName, $attributes)) {
                unset($instance->{$propertyName});

                continue;
            }

            $value = $attributes[$propertyName];

            $property = $reflector->getProperty($propertyName);
            if (!$property) {
                continue;
            }

            $propertyType = $property->type();
            if (!$propertyType || !($propertyType instanceof \ReflectionNamedType)) {
                continue;
            }

            if (!is_iterable($value) && $propertyType->getName() !== $value::class) {
                continue;
            }

            if (is_iterable($value) && $propertyType->getName() === Collection::class) {
                $value = Collection::wrap($value);
            } elseif (is_iterable($value) && $propertyType->getName() === "array") {
                $value = iterator_to_array($value);
            }

            $property->setValue($instance, $value);
        }
    }

    /**
     * Get all the model properties on the given reflector.
     *
     * @param ReflectedType<TModel>     $reflector
     *
     * @return array<string,ReflectedProperty>
     */
    protected function resolveModelProperties(ReflectedType $reflector): array
    {
        if (isset($this->properties[$reflector->name])) {
            return $this->properties[$reflector->name];
        }

        $properties = [];
        $className = $reflector->name;

        foreach ($className::mappable() as $propertyName) {
            $property = $reflector->getProperty($propertyName);

            if ($property) {
                $properties[$propertyName] = $property;
            }
        }

        $this->properties[$reflector->name] = $properties;

        return $properties;
    }

    /**
     * Get all the model relations on the given reflector.
     *
     * @param class-string<TModel>  $model
     *
     * @return array<string,HasRelation<TModel>>
     */
    protected function resolveModelRelations(string $model): array
    {
        if (isset($this->relations[$model])) {
            return $this->relations[$model];
        }

        $relations = $this->relations[$model] = $model::relations();

        return $relations;
    }
}

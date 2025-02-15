<?php

namespace Brickhouse\Database\Transposer;

use Brickhouse\Database\Transposer\Relations\Relation;
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
     * @var array<class-string<TModel>,array<string,Relation<TModel>>>
     */
    protected array $relations = [];

    /**
     * Creates a new instance of the model and fills it with the given properties.
     *
     * @param class-string<TModel>  $model          Name of the model to create an instance of.
     * @param array<string,mixed>   $attributes     Properties to fill into the model on creation.
     * @param bool                  $validate       Whether to validate the model after creation. Defaults to `true`.
     *
     * @return TModel
     */
    public function create(string $model, array $attributes, bool $validate = true): Model
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

        $this->loadModelAttributes($instance, $attributes);
        $this->loadRelatedAttributes($reflector, $instance, $attributes);

        $instance->normalizeAllAttributes();

        // Validate all the model attributes *after* normalizing the attributes.
        if ($validate) {
            $instance->validateAllAttributes();
        }

        return $instance;
    }

    /**
     * Load all the attributes in `$attributes` into the model instance.
     *
     * @param TModel                $instance
     * @param array<string,mixed>   $attributes     Properties to fill into the model on creation.
     */
    protected function loadModelAttributes(Model $instance, array $attributes): void
    {
        $properties = $instance->getMappableAttributeProperties();
        $defaults = $instance::attributeDefaults();

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

            if ($instance->isModelRelation($property->name)) {
                continue;
            }

            $property->setValue($instance, $attributes[$property->name]);
        }

        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, $properties)) {
                continue;
            }

            // If the property doesn't exist on the model instance, set it as an auxiliary value.
            if (!property_exists($instance, $key)) {
                $instance->addAuxiliaryAttribute($key, $value);
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
        $relations = $instance->getModelRelations();

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
}

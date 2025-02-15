<?php

namespace Brickhouse\Database\Transposer;

use Brickhouse\Database\Transposer\Exceptions\UnsupportedRelationException;
use Brickhouse\Reflection\ReflectedType;
use Brickhouse\Support\Collection;

class ChangeTracker
{
    /**
     * Defines all applied references in the current change tracker.
     *
     * @var array<int,Model>
     */
    private array $appliedReferences = [];

    /**
     * Saves the given model and all relevant relations to the database.
     *
     * @template TModel of Model
     *
     * @param TModel    $model
     * @param bool      $validate       Whether to validate the model or not. Defaults to `true`.
     *
     * @return TModel
     */
    public function save(Model $model, bool $validate = true): Model
    {
        // Normalize all the attributes on the model before saving it,
        // to preserve integrity on the model.
        $model->normalizeAllAttributes();

        // Validate all the model attributes *after* normalizing the attributes.
        // If the model isn't valid, don't attempt to save to database and just return.
        if ($validate && !$model->validateAllAttributes()) {
            return $model;
        }

        $this->applyRelationReferences($model);

        $query = $model->query();

        if ($model->exists) {
            $model = $query->update($model);
        } else {
            $model = $query->insert($model);
        }

        $model->setOriginalAttributes(
            $model->getProperties(include_relations: true)
        );

        /** @var TModel $model */
        return $model;
    }

    /**
     * Saves the given models and all relevant relations to the database.
     *
     * @template TModel of Model
     *
     * @param Collection<int,TModel>    $models
     *
     * @return Collection<int,TModel>
     */
    public function saveMany(Collection $models): Collection
    {
        /** @phpstan-ignore return.type (I'm unsure why PHPStan is complaining about this one.) */
        return $models->map($this->save(...));
    }

    /**
     * Applies relations to all nested relations on the given model, making them all two-way binding.
     *
     * @template TModel of Model
     *
     * @param TModel    $model
     *
     * @return void
     */
    protected function applyRelationReferences(Model $model): void
    {
        // If we've already applied the relation on the model, skip it.
        // This helps prevent infinite loops, in which a model references another model (e.g. via HasOne), which
        // then references back to the previous via a BelongsTo reference.
        if (in_array($model, $this->appliedReferences)) {
            return;
        }

        foreach ($model->getModelRelations() as $property => $relation) {
            // If the relation isn't set on the model, we have no relations to update.
            if (!isset($model->$property)) {
                continue;
            }

            foreach (Collection::wrap($model->$property) as $relatedModel) {
                // Attempt to guess the name of the relation on the target model, which matches the current relation.
                $relatedModelAttribute = $relation->guessMatchingRelation($model);

                // We need to use reflection to get the attribute type, as it might be uninitialized on the model.
                $attributePropertyType = $this->getModelAttributeType($relatedModel, $relatedModelAttribute);

                // Ensure that the attribute is set on the model, as we'd otherwise get an uninitialized error
                // when trying to push a new model onto property.
                $this->ensureRelationalAttributeExists($relatedModel, $relatedModelAttribute);

                // Set or add the model instance to the relational models property.
                if ($attributePropertyType === 'array') {
                    $relatedModel->$relatedModelAttribute[] = $model;
                } else if ($attributePropertyType === Collection::class) {
                    $relatedModel->$relatedModelAttribute->push($model);
                } else {
                    $relatedModel->$relatedModelAttribute = $model;
                }

                $this->appliedReferences[] = $relatedModel;
            }
        }

        $this->appliedReferences[] = $model;
    }

    /**
     * Gets the type of the given attribute on the given model.
     *
     * @param Model     $model
     * @param string    $property
     *
     * @return string
     */
    protected function getModelAttributeType(Model $model, string $property): string
    {
        $reflector = new ReflectedType($model::class);
        $propertyType = $reflector->getProperty($property)->type();

        if (!$propertyType instanceof \ReflectionNamedType) {
            throw new UnsupportedRelationException($model::class, $property, $propertyType ?? 'unknown');
        }

        return $propertyType->getName();
    }

    /**
     * Ensure that the relational attribute `$property` is set on `$model`.
     *
     * If the property is a type of array or collection, an empty traversable is set, according to the type.
     *
     * @param Model     $model
     * @param string    $property
     *
     * @return void
     */
    protected function ensureRelationalAttributeExists(Model $model, string $property): void
    {
        // If the property is already set, ignore it.
        if (isset($model->$property)) {
            return;
        }

        $propertyType = $this->getModelAttributeType($model, $property);

        if ($propertyType === 'array') {
            $model->$property = [];
        } else if ($propertyType === Collection::class) {
            $model->$property = Collection::empty();
        } else if (!is_subclass_of($propertyType, Model::class)) {
            throw new UnsupportedRelationException($model::class, $property, $propertyType);
        }
    }
}

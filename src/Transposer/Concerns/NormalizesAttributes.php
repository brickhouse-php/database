<?php

namespace Brickhouse\Database\Transposer\Concerns;

use Brickhouse\Database\Transposer\Exceptions\ModelNormalizationException;
use Brickhouse\Database\Transposer\Model;

/**
 * @template TModel of Model
 *
 * @property null|int|string    $id
 *
 * @phpstan-require-extends Model
 */
trait NormalizesAttributes
{
    /** @use HasAttributes<TModel> */
    use HasAttributes;

    /** @use HasModelQuery<TModel> */
    use HasModelQuery;

    /**
     * Normalization rules for the model.
     *
     * @return array<string,callable(mixed):mixed|array<array-key,mixed>>
     */
    public function normalize(): array
    {
        return [];
    }

    /**
     * Normalizes all attributes in the model, according to the `normalize` method on the model.
     *
     * @return void
     */
    public function normalizeAllAttributes(): void
    {
        foreach ($this->normalize() as $attribute => $callbacks) {
            if (!is_string($attribute)) {
                throw new ModelNormalizationException(
                    $this::class,
                    "normalize() must return a keyed array of rules, found indexed array."
                );
            }

            $callbacks = array_wrap($callbacks);

            $this->normalizeAttribute($attribute, $callbacks);
        }
    }

    /**
     * Normalizes a single attribute on the model, according to the given rules.
     *
     * @template TValue
     *
     * @param string $attribute                     $attribute  Name of the attribute to normalize.
     * @param array<int,callable(TValue):TValue>    $rules      Rules to normalize the value of the attribute.
     *
     * @return void
     */
    private function normalizeAttribute(string $attribute, array $rules): void
    {
        $model = $this::class;

        if (!isset($this->$attribute)) {
            throw new \InvalidArgumentException("Cannot normalize {$model}::{$attribute}: attribute is unset.");
        }

        $value = $this->$attribute;
        foreach ($rules as $rule) {
            $value = $rule($value);
        }

        $this->$attribute = $value;
    }
}

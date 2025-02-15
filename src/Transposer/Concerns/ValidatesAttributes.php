<?php

namespace Brickhouse\Database\Transposer\Concerns;

use Brickhouse\Database\Transposer\Ignore;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Validation\Validator;

/**
 * @template TModel of Model
 *
 * @phpstan-require-extends Model
 * @phpstan-import-type     RuleSet from Validator
 */
trait ValidatesAttributes
{
    /** @use HasAttributes<TModel> */
    use HasAttributes;

    /**
     * Defines all the validation errors on the model.
     *
     * @var array<string,list<string>>
     */
    #[Ignore]
    public private(set) array $errors = [];

    /**
     * Defines whether the model passed validation.
     *
     * @var bool
     */
    #[Ignore]
    public private(set) bool $valid = true;

    /**
     * Defines whether the model validation failed.
     *
     * @var bool
     */
    #[Ignore]
    public private(set) bool $invalid = false;

    /**
     * Validation rules for the model.
     *
     * @return RuleSet
     */
    public function validate(): array
    {
        return [];
    }

    /**
     * Normalizes all attributes in the model, according to the `normalize` method on the model.
     *
     * @return bool         Returns `true` if the model is valid. Otherwise, `false`.
     */
    public function validateAllAttributes(): bool
    {
        // If the validation package isn't installed, act as if everything is valid.
        if (!class_exists(Validator::class)) {
            return true;
        }

        $rules = $this->validate();
        $validator = new Validator($rules);

        $result = $validator->validate($this);

        $this->errors = $result->errors;
        $this->valid = $result->valid;
        $this->invalid = $result->invalid;

        return $this->valid;
    }
}

<?php

namespace Brickhouse\Database\Transposer\Concerns;

use Brickhouse\Database\Transposer\Model;

/**
 * @template TModel of Model
 *
 * @property null|int|string    $id
 *
 * @phpstan-require-extends Model
 */
trait HasRelations
{
    /** @use HasAttributes<TModel> */
    use HasAttributes;

    /** @use HasModelQuery<TModel> */
    use HasModelQuery;

    /**
     * Loads the given relation(s) into the current model.
     *
     * @param string    $relations
     *
     * @return self
     */
    public function load(string ...$relations): self
    {
        $loadedModel = self::query()
            ->with(...$relations)
            ->find($this->id);

        // Load the retrieved relations from the returned model into the current model.
        foreach ($relations as $relation) {
            // We don't handle nested relations, as they're already
            // populated by the model query builder.
            if (str_contains($relation, '.')) {
                continue;
            }

            $this->$relation = $loadedModel->$relation;
        }

        return $this;
    }
}

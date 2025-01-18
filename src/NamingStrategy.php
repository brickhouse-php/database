<?php

namespace Brickhouse\Database;

/**
 * @template TModel of Model
 */
interface NamingStrategy
{
    /**
     * Gets the class name of the model.
     *
     * @var class-string<TModel>
     */
    public string $model { get; }

    /**
     * Names the primary key of the model.
     *
     * @return string
     */
    public function key(): string;

    /**
     * Names the name of the table to store the model.
     *
     * @return string
     */
    public function table(): string;

    /**
     * Names foreign keys which refer to instances of this model.
     *
     * @return string
     */
    public function foreignKey(): string;
}

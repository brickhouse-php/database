<?php

namespace Brickhouse\Database\Transposer\Naming;

use Brickhouse\Database\Transposer\NamingStrategy;
use Brickhouse\Database\Transposer\Model;
use Brickhouse\Reflection\ReflectedType;
use Brickhouse\Support\StringHelper;

/**
 * @template TModel of Model
 *
 * @implements NamingStrategy<TModel>
 */
class SnakeCaseNamingStrategy implements NamingStrategy
{
    /**
     * @param class-string<TModel>      $model  Class name of the model.
     */
    public function __construct(
        public string $model,
    ) {}

    public function key(): string
    {
        if (($value = new ReflectedType($this->model)->getStaticPropertyValue('key')) !== null) {
            return $value;
        }

        return "id";
    }

    public function table(): string
    {
        $className = new \ReflectionClass($this->model)->getShortName();
        $tableName = StringHelper::from($className)
            ->snake()
            ->pluralize();

        return $tableName;
    }

    public function foreignKey(): string
    {
        $className = new \ReflectionClass($this->model)->getShortName();
        $keyName = StringHelper::from($className)->snake() . '_id';

        return $keyName;
    }
}

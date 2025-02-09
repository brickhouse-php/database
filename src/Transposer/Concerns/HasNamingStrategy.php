<?php

namespace Brickhouse\Database\Transposer\Concerns;

use Brickhouse\Database\Transposer\Model;
use Brickhouse\Database\Transposer\Naming\SnakeCaseNamingStrategy;
use Brickhouse\Database\Transposer\NamingStrategy;
use Brickhouse\Reflection\ReflectedType;

/**
 * @template TModel of Model
 */
trait HasNamingStrategy
{
    /**
     * Contains an instance of the default naming strategy.
     *
     * @var null|NamingStrategy<TModel>
     */
    private static null|NamingStrategy $defaultNamingStrategy;

    /**
     * Gets the name of the primary key column.
     */
    public static function key(): string
    {
        return static::naming()->key();
    }

    /**
     * Gets the name of the table to store the model in.
     */
    public static function table(): string
    {
        return static::naming()->table();
    }

    /**
     * Gets the naming strategy for the model.
     *
     * @return NamingStrategy<self>
     */
    public static function naming(): NamingStrategy
    {
        if (($value = new ReflectedType(static::class)->getStaticPropertyValue('naming')) !== null) {
            return $value;
        }

        return (self::$defaultNamingStrategy ?? new SnakeCaseNamingStrategy(static::class));
    }
}

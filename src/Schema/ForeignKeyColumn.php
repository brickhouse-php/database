<?php

namespace Brickhouse\Database\Schema;

use Brickhouse\Database\Schema\Concerns;
use Brickhouse\Reflection\ReflectedType;

class ForeignKeyColumn extends Column
{
    use Concerns\HasConflictClause;

    /**
     * Syntax for the which action to execute when the foreign key is updated.
     */
    public private(set) null|ForeignKeyTrigger $onUpdate = null;

    /**
     * Syntax for the which action to execute when the foreign key is delete.
     */
    public private(set) null|ForeignKeyTrigger $onDelete = null;

    public function __construct(
        protected readonly Column $column,
        public readonly string $table,
        public readonly string $key,
    ) {
        parent::__construct(
            $column->blueprint,
            $column->name,
            $column->type,
            $column->parameters
        );

        $reflector = new ReflectedType($column::class);
        foreach ($reflector->getProperties() as $property) {
            if ($property->public() && $property->hooked()) {
                $property->setValue($this, $property->value($column));
            }
        }
    }

    /**
     * Defines which action to execute when the foreign key is updated.
     *
     * @param ForeignKeyTrigger     $action
     *
     * @return self
     */
    public function onUpdate(ForeignKeyTrigger $action): self
    {
        $this->onUpdate = $action;
        return $this;
    }

    /**
     * Defines that the column should cascade when the foreign key is updated.
     *
     * @return self
     */
    public function cascadeOnUpdate(): self
    {
        return $this->onUpdate(ForeignKeyTrigger::Cascade);
    }

    /**
     * Defines that the column should ignore when the foreign key is updated.
     *
     * @return self
     */
    public function ignoreOnUpdate(): self
    {
        return $this->onUpdate(ForeignKeyTrigger::Ignore);
    }

    /**
     * Defines that the foreign key should restricted from being modified, when one or more child keys exist.
     *
     * @return self
     */
    public function restrictOnUpdate(): self
    {
        return $this->onUpdate(ForeignKeyTrigger::Restrict);
    }

    /**
     * Defines that the column should be set to `NULL` when the foreign key is updated.
     *
     * @return self
     */
    public function nullOnUpdate(): self
    {
        return $this->onUpdate(ForeignKeyTrigger::SetNull);
    }

    /**
     * Defines that the column should be set to it's default value when the foreign key is updated.
     *
     * @return self
     */
    public function defaultOnUpdate(): self
    {
        return $this->onUpdate(ForeignKeyTrigger::SetDefault);
    }

    /**
     * Defines which action to execute when the foreign key is updated.
     *
     * @param ForeignKeyTrigger     $action
     *
     * @return self
     */
    public function onDelete(ForeignKeyTrigger $action): self
    {
        $this->onDelete = $action;
        return $this;
    }

    /**
     * Defines that the column should cascade when the foreign key is deleted.
     *
     * @return self
     */
    public function cascadeOnDelete(): self
    {
        return $this->onDelete(ForeignKeyTrigger::Cascade);
    }

    /**
     * Defines that the column should ignore when the foreign key is deleted.
     *
     * @return self
     */
    public function ignoreOnDelete(): self
    {
        return $this->onDelete(ForeignKeyTrigger::Ignore);
    }

    /**
     * Defines that the foreign key should restricted from being deleted, when one or more child keys exist.
     *
     * @return self
     */
    public function restrictOnDelete(): self
    {
        return $this->onDelete(ForeignKeyTrigger::Restrict);
    }

    /**
     * Defines that the column should be set to `NULL` when the foreign key is deleted.
     *
     * @return self
     */
    public function nullOnDelete(): self
    {
        return $this->onDelete(ForeignKeyTrigger::SetNull);
    }

    /**
     * Defines that the column should be set to it's default value when the foreign key is deleted.
     *
     * @return self
     */
    public function defaultOnDelete(): self
    {
        return $this->onDelete(ForeignKeyTrigger::SetDefault);
    }
}

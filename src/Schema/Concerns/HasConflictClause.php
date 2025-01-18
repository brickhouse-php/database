<?php

namespace Brickhouse\Database\Schema\Concerns;

trait HasConflictClause
{
    /**
     * Defines the action to take when a column causes a conflict.
     */
    public protected(set) null|string $onConflict = null;

    /**
     * Marks the column to rollback when causing a conflict.
     *
     * @return self
     */
    public function rollbackOnConflict(): self
    {
        $this->onConflict = "ROLLBACK";
        return $this;
    }

    /**
     * Marks the column to abort when causing a conflict.
     *
     * @return self
     */
    public function abortOnConflict(): self
    {
        $this->onConflict = "ABORT";
        return $this;
    }

    /**
     * Marks the column to fail the query when causing a conflict.
     *
     * @return self
     */
    public function failOnConflict(): self
    {
        $this->onConflict = "FAIL";
        return $this;
    }

    /**
     * Marks the column to ignore conflicts when causing a conflict.
     *
     * @return self
     */
    public function ignoreOnConflict(): self
    {
        $this->onConflict = "IGNORE";
        return $this;
    }

    /**
     * Marks the column to replace the conflicting row when causing a conflict.
     *
     * @return self
     */
    public function replaceOnConflict(): self
    {
        $this->onConflict = "REPLACE";
        return $this;
    }
}

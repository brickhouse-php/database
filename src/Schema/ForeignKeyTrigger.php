<?php

namespace Brickhouse\Database\Schema;

enum ForeignKeyTrigger
{
    /**
     * Defines that the column should ignore when the foreign key is deleted.
     */
    case Ignore;

    /**
     * Defines that the foreign key should restricted from being deleted, when one or more child keys exist.
     */
    case Restrict;

    /**
     * Defines that the column should be set to `NULL` when the foreign key is deleted.
     */
    case SetNull;

    /**
     * Defines that the column should be set to it's default value when the foreign key is deleted.
     */
    case SetDefault;

    /**
     * Defines that the column should cascade when the foreign key is deleted.
     */
    case Cascade;
}

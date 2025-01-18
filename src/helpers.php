<?php

if (! function_exists("migrations_path")) {
    /**
     * Gets the applications database migrations directory.
     *
     * @param string    $path   Optional path to append to the database migrations path.
     *
     * @return string
     */
    function migrations_path(?string ...$path): string
    {
        return resource_path("migrations", ...$path);
    }
}

<?php

namespace DSL;

abstract class Operation {
    public const DESCRIPTION = "### Operation have no description ###";

    final public static function NAME() : string { return strtolower(substr(strrchr(static::class, "\\"), 1)); }

    /**
     * @param array $parameters
     *
     * @return void
     *
     * @throws \Exception
     */
    abstract public static function execute(array $parameters) : void;
}

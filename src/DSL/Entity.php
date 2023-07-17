<?php

namespace DSL;

abstract class Entity {
    final protected function __construct() { }

    /**
     * Init new class object
     *
     * @return static
     */
    final public static function new() : static { return new static(); }

    final protected function __return() : static { return $this; }

    /**
     * Generate descriptor for class
     *
     * @return \DSL\EntityDescriptor
     *
     * @throws \Exception
     */
    final public static function getDescription() : \DSL\EntityDescriptor { return \DSL\EntityDescriptor::generate(static::class); } // TODO: wrong versions

    /**
     * Method for finalization
     *
     * @return mixed
     */
    abstract public function method__apply():mixed;
}

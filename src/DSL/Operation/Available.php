<?php

namespace DSL\Operation;

final class Available {
    protected const DEFAULTS = [
        "where" => \DSL\Operation\Where::class,
        "limit" => \DSL\Operation\Limit::class,
    ];

    /**
     * Available operator classes
     * <code>
     * [
     *      "<name>" => "<classname>",
     *      ...
     * ]
     *
     * @var array
     */
    protected static array $_classes = self::DEFAULTS;

    /**
     * Add new available operation class
     *
     * @param \DSL\Operation[] $operatorClass
     *
     * @return string|static return Available class name for chaining call
     *
     * @throws \Exception\DSL\Entity\AlreadyDefined
     */
    public static function addOperator(\DSL\Operation ...$operatorClass) : string|static {
        foreach ($operatorClass as $_operatorClass) {
            if (static::operationClassDefined($_operatorClass) === true) {
                throw new \Exception\DSL\Entity\AlreadyDefined();
            }

            static::$_classes[$_operatorClass::NAME()] = $_operatorClass;
        }

        return static::class;
    }

    /**
     * Reset available operation classes to default state list
     *
     * @return string|static return Available class name for chaining call
     */
    public static function reset() : string|static {
        static::$_classes[] = static::DEFAULTS;

        return static::class;
    }

    /**
     * Check define operation class or not
     *
     * @param \DSL\Operation $operatorClass
     *
     * @return bool
     */
    public static function operationClassDefined(\DSL\Operation $operatorClass) : bool { return static::operationNameDefined($operatorClass::NAME()); }

    /**
     * Check define operation class or not
     *
     * @param string $operatorName
     *
     * @return bool
     */
    public static function operationNameDefined(string $operatorName) : bool { return isset(static::$_classes[$operatorName]) === true; }

    /**
     * Get available operator classes
     * <code>
     * [
     *      "<name>" => "<classname>",
     *      ...
     * ]
     * </code>
     *
     * @return array
     */
    public static function AvailableOperations() : array { return static::$_classes; }

    /**
     * Get available operator classes
     *
     * @return \DSL\Operation[]
     */
    public static function AvailableOperationNames() : array { return array_keys(static::AvailableOperations()); }

    /**
     * Get available operator classes
     *
     * @return \DSL\Operation[]
     */
    public static function AvailableOperationClasses() : array { return array_values(static::AvailableOperations()); }

    /**
     * Get operation class by operation name
     *
     * @param string $operationName
     *
     * @return \DSL\Operation
     *
     * @throws \Exception\DSL\Entity\Unknown
     */
    public static function getOperationClassByOperationName(string $operationName) : \DSL\Operation {
        return static::$_classes[$operationName] ?? throw new \Exception\DSL\Entity\Unknown(message: $operationName);
    }
}

<?php

namespace DSL\Entity;

final class Pool {
    protected const DEFAULT_ENTITIES = [
        \DSL\Entity\SimpleCalculator::class,
    ];

    protected static self $__instance;

    /**
     * Available operator classes
     * <code>
     * [
     *      "<classname>" => <EntityDescriptor>,
     *      ...
     * ]
     *
     * @var \DSL\EntityDescriptor[]
     */
    protected array $_entityDescriptors = [];

    /**
     * @throws \Exception
     */
    protected function __construct() { static::reset(); }

    /**
     * @throws \Exception
     */
    public static function Instance() : static { return static::$__instance ??= new static(); }

    protected function __return() : static { return $this; }

    /**
     * Add new available operation class
     *
     * @param \DSL\Entity[]|string[] $entities
     *
     * @return static
     *
     * @throws \Exception\DSL\Entity\AlreadyDefined
     * @throws \Exception
     */
    public function addEntity(\DSL\Entity|string ...$entities) : static {
        foreach ($entities as $entity) {
            if (static::isDefinedEntity($entity) === true) {
                throw new \Exception\DSL\Entity\AlreadyDefined();
            }

            $entityDescriptor = \DSL\EntityDescriptor::generate($entity);
            $this->_entityDescriptors[$entityDescriptor->EntityName()] = $entityDescriptor;
        }

        return static::__return();
    }

    /**
     * Reset available operation classes to default state list
     *
     * @return static
     *
     * @throws \Exception
     */
    public function reset() : static {
        foreach (static::DEFAULT_ENTITIES as $entity) {
            $entityDescriptor = \DSL\EntityDescriptor::generate($entity);
            $this->_entityDescriptors[$entityDescriptor->EntityName()] = $entityDescriptor;
        }

        return static::__return();
    }

    /**
     * Check define operation class or not
     *
     * @param string $entity
     *
     * @return bool
     */
    public function isDefinedEntity(string $entity) : bool { return array_key_exists($entity, $this->_entityDescriptors) === true; }

    /**
     * Check define operation class or not
     *
     * @param string $entity
     * @param string $method
     *
     * @return bool
     */
    public function isDefinedEntityMethod(string $entity, ?string $method) : bool { return $this->_entityDescriptors[$entity]?->hasMethod($method) ?? false; }

    /**
     * Get available operator classes
     *
     * @return \DSL\EntityDescriptor[]
     */
    public function DefinedEntities() : array { return array_values($this->_entityDescriptors); }

    /**
     * @param string|\DSL\Entity $entity
     * @param array              $methods
     *
     * @return mixed
     *
     * @throws \Exception\DSL\Entity\Unknown
     */
    public function tryToExecute(string|\DSL\Entity $entity, array $methods) : mixed {
        if (static::isDefinedEntity($entity) === false) {
            throw new \Exception\DSL\Entity\Unknown("Unknown entity `{$entity}`");
        }

        $entityDescriptor = $this->_entityDescriptors[$entity];

        return $entityDescriptor->tryToExecute($methods);
    }
}

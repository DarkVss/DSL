<?php

namespace DSL;

final class EntityDescriptor {
    protected function __construct() { }

    /**
     * Generate descriptor
     *
     * @param \DSL\Entity|string $classname
     *
     * @return static
     *
     * @throws \Exception
     */
    public static function generate(\DSL\Entity|string $classname) : static {
        if (is_subclass_of($classname, \DSL\Entity::class) === false) {
            throw new \Exception\DSL\EntityDescriptor\NotSubClass(message: "Descriptor can only be generated for child classes " . \DSL\Entity::class, code: 400);
        }

        $instance = new static();

        $instance->_entityName = \DSL\Helpers::extractClassname($classname);
        $instance->_classname = $classname;
        $instance->_generate();

        return $instance;
    }

    protected string $_entityName;
    protected \DSL\Entity|string $_classname;
    protected string $_comment;
    /**
     * @var \DSL\EntityDescriptor\Method[] $_methods
     */
    protected array $_methods = [];

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function _generate() : void {
        try {
            $reflector = new \ReflectionClass($this->_classname);

            $this->_comment = \DSL\Helpers::extractDocComment($reflector->getDocComment() ?: '') ?? "### Entity have no description ###";
            $methods = array_filter(
                $reflector->getMethods(\ReflectionMethod::IS_PUBLIC),
                fn(\ReflectionMethod $method) => preg_match("/\A" . \DSL\EntityDescriptor\Method::METHOD_NAME_BEGIN . "[a-zA-Z_0-9]+\z/", $method->getName()) === 1
            );
            if (count($methods) === 0) {
                throw new \Exception\DSL\Entity\NoMethods(message: static::EntityName() . " have no one methods", code: 500);
            }
            $methods = array_map(fn(\ReflectionMethod $method) => \DSL\EntityDescriptor\Method::parse($method), $methods);
            $this->_methods = array_combine(
                array_map(fn(\DSL\EntityDescriptor\Method $method) => $method->Name(), $methods),
                $methods
            );
        } catch (\ReflectionException $e) {
            throw new \Exception\DSL\EntityDescriptor\BrokenClass(message: "Can't parse '{$this->_classname}' class", code: 500, previous: $e);
        }
    }

    /**
     * @return string
     */
    public function EntityName() : string { return $this->_entityName; }

    /**
     * @return \DSL\Entity|string
     */
    public function Classname() : \DSL\Entity|string { return $this->_classname; }

    /**
     * @return string
     */
    public function Comment() : string { return $this->_comment; }

    /**
     * @return array
     */
    public function Methods() : array { return $this->_methods; }

    public function hasMethod(string $method) : bool { return key_exists($method, $this->_methods) === true; }

    /**
     * @param array $methods
     *
     * @return mixed
     *
     * @throws \Exception\DSL\Entity\Unknown
     */
    public function tryToExecute(array $methods) : mixed {
        foreach ($methods as $method) {
            if (static::hasMethod($method["name"] ?? null) === false) {
                throw new \Exception\DSL\Entity\Unknown("Undefined method `" . static::EntityName() . ($method["name"] ?? null) . "`");
            }
        }

        $entity = static::Classname()::new();
        foreach ($methods as $method) {
            $entity = $entity->{$this->_methods[$method["name"]]->Name(false)}(...$method["parameters"]);
        }

        return $entity->apply();
    }
}

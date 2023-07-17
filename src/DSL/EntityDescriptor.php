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
            throw new \Exception(message: "Descriptor can only be generated for child classes " . \DSL\Entity::class, code: 400);
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
            $this->_methods = array_map(
                fn(\ReflectionMethod $method) => \DSL\EntityDescriptor\Method::parse($method),
                array_filter(
                    $reflector->getMethods(\ReflectionMethod::IS_PUBLIC),
                    fn(\ReflectionMethod $method) => preg_match("/\A" . \DSL\EntityDescriptor\Method::METHOD_NAME_BEGIN . "[a-zA-Z_0-9]+\z/", $method->getName()) === 1
                )
            );
        } catch (\ReflectionException $e) {
            throw new \Exception(message: "Can't parse '{$this->_classname}' class", code: 500, previous: $e);
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

    public function hasMethod(string $method) : bool {
        foreach (static::Methods() as $_method) {
            if ($_method->Name() === $method) {
                return true;
            }
        }

        return false;
    }
}

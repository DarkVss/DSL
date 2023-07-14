<?php

namespace DSL\EntityDescriptor\Method;

final class Parameter {
    protected string $_name;
    protected string $_type;
    protected bool $_isOptional;
    protected bool $_isAllowedNull;

    public function __construct(string $name, string $type, bool $isOptional, bool $isAllowedNull) {
        $this->_name = $name;
        $this->_type = $type;
        $this->_isOptional = $isOptional;
        $this->_isAllowedNull = $isAllowedNull;
    }

    /**
     * @return string
     */
    public function Name() : string { return $this->_name; }

    /**
     * @return string
     */
    public function Type() : string { return $this->_type; }

    /**
     * @return bool
     */
    public function isIsOptional() : bool { return $this->_isOptional; }

    /**
     * @return bool
     */
    public function isIsAllowedNull() : bool { return $this->_isAllowedNull; }
}

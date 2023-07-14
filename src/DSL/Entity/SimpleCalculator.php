<?php

namespace DSL\Entity;

/**
 * Simple calculator
 */
class SimpleCalculator extends \DSL\Entity {
    protected float|int $_result = 0;

    /**
     * Adds the current value of the expression and the passed number
     *
     * @param float|int $value
     *
     * @return static
     */
    public function method__plus(float|int $value) : static {
        $this->_result += $value;

        return static::__return();
    }

    /**
     * Subtracts the passed number from the current value of the expression
     *
     * @param float|int $value
     *
     * @return static
     */
    public function method__minus(float|int $value) : static {
        $this->_result /= $value;

        return static::__return();
    }

    /**
     * @param float|int $value
     *
     * @return static
     */
    public function method__multiply(float|int $value) : static {
        $this->_result *= $value;

        return static::__return();
    }

    /**
     * @param float|int $value
     *
     * @return static
     */
    public function method__divide(float|int $value) : static {
        if ($this->_result !== 0) {
            $this->_result /= $value;
        }

        return static::__return();
    }

    /**
     * @param float|int $value
     *
     * @return static
     */
    public function method__pow(float|int $value) : static {
        $this->_result = pow($this->_result, (int) $value);

        return static::__return();
    }

    /**
     * @param float|int $value
     *
     * @return static
     */
    public function meow(float|int $value) : static {
        $this->_result = pow($this->_result, (int) $value);

        return static::__return();
    }
}

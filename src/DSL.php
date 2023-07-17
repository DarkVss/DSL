<?php

final class DSL {
    /**
     * Use cast for array, numeric(detect float only with non-zero value after point), boolean, null parameters or not
     *
     * @var bool
     */
    protected bool $_useTypeCast = false;
    /**
     * Basic descriptors for instructions
     *
     * @var array
     */
    protected array $_instructions = [];

    protected function __return() : static { return $this; }

    public function __construct() { }

    /**
     * Init new class instance
     *
     * @return static
     */
    public static function init() : static { return new static(); }

    /**
     * Set flag - use cast for array, numeric(detect float only with non-zero value after point), boolean, null parameters or not
     *
     * @param bool $useTypeCast
     *
     * @return static
     */
    public function useTypeCast(bool $useTypeCast) : static {
        $this->_useTypeCast = $useTypeCast;

        return static::__return();
    }

    /**
     * Get flag - used cast for array, numeric(detect float only with non-zero value after point), boolean, null parameters or not
     *
     * @return bool
     */
    public function usingTypeCast() : bool { return $this->_useTypeCast; }


    /**
     * Try parse instructions file
     *
     * @param string $instructionsFile
     *
     * @return static
     *
     * @throws \Exception\DSL\InstructionsFile\NotFound
     * @throws \Exception\DSL\InstructionsFile\UnavailableToRead
     * @throws \Exception\DSL\InstructionsFile\ParseFail
     * @throws \Exception\DSL\Instruction\NotFound
     */
    public function parseFile(string $instructionsFile) : static {
        if (is_file($instructionsFile) === false) {
            throw new \Exception\DSL\InstructionsFile\NotFound();
        }

        $instructionsCode = @file_get_contents($instructionsFile);
        if ($instructionsCode === false) {
            throw new \Exception\DSL\InstructionsFile\UnavailableToRead();
        }

        return static::__parse($instructionsCode);
    }

    /**
     * Try parse instructions code
     *
     * @param string $instructionsCode
     *
     * @return static
     *
     * @throws \Exception\DSL\InstructionsFile\ParseFail
     * @throws \Exception\DSL\Instruction\NotFound
     */
    public function parseCode(string $instructionsCode) : static { return static::__parse($instructionsCode); }

    /**
     * Parse instruction code to basic descriptors
     *
     * @param string $instructionsCode
     *
     * @return static
     *
     * @throws \Exception\DSL\InstructionsFile\ParseFail
     * @throws \Exception\DSL\Instruction\NotFound
     */
    public function __parse(string $instructionsCode) : static {
        $this->_instructions = \DSL\Lexer::parse($instructionsCode, static::usingTypeCast());

        if (count($this->_instructions) === 0) {
            throw new \Exception\DSL\Instruction\NotFound();
        }

        return static::__return();
    }

    /**
     * Execute instruction
     *
     * @return array
     *
     * @throws \Exception\DSL\Instruction\NotSet
     * @throws \Exception\DSL\Instruction\Fail
     */
    public function run() : array {
        if (count($this->_instructions) === 0) {
            throw new \Exception\DSL\Instruction\NotSet();
        }

        $result = [];
        $instructionIndex = 0;

        try {
            for (; $instructionIndex < count($this->_instructions); $instructionIndex++) {
                $result[] = \DSL\Entity\Pool::Instance()->tryToExecute($this->_instructions[$instructionIndex]["entity"], $this->_instructions[$instructionIndex]["methods"]);

            }
        } catch (\Exception $e) {
            throw new \Exception\DSL\Instruction\Fail("Failed execution instruction at #" . ($instructionIndex + 1) . " entity - '{$this->_instructions[$instructionIndex]["operation"]}'", previous: $e);
        }

        return $result;
    }
}

<?php

namespace DSL;

final class Lexer {
    public const TOKEN_PARAMETERS_START = "(";
    public const TOKEN_PARAMETERS_END = ")";
    public const TOKEN_PARAMETERS_DELIMITER = ',';
    public const TOKEN_STRING_DELIMITER = "\"";
    public const TOKEN_STRING_ESCAPE = '\\';

    public const VALID_ESCAPE_CHARACTERS = ["\"", "\\"];

    /**
     * Parse code
     *
     * @param string $code        Code string
     * @param bool   $useTypeCast cast numeric, boolean, null value or not
     *
     * @return array
     *
     * @throws \Exception\DSL\InstructionsFile\ParseFail
     */
    public static function parse(string $code, bool $useTypeCast = false) : array {
        $instructions = [];

        try {
            $_instructions = array_map("trim", explode("\n", str_replace("\r", '', $code)));
            for ($row = 0; $row < count($_instructions); $row++) {
                $tokens = mb_str_split($_instructions[$row]);

                $inputLength = count($tokens);

                $operationName = '';
                $currentPart = '';
                $instructionParameters = [];
                $isParametersParsingProcess = false;
                $isParameterStringParsingProcess = false;

                $stringStartPosition = -1;
                $inputListStartPosition = -1;

                for ($column = 0; $column < $inputLength; $column++) {
                    $currentPosition = ($row + 1) . ":" . ($column + 1);
                    $current = $tokens[$column];
                    $previous = $tokens[$column - 1] ?? null;
                    $next = $tokens[$column + 1] ?? null;

                    if ($previous === null) {
                        $exception = match ($current) {
                            default                            => null,
                            static::TOKEN_PARAMETERS_END       => "TOKEN_INPUT_END",
                            static::TOKEN_PARAMETERS_START     => "TOKEN_INPUT_START",
                            static::TOKEN_STRING_DELIMITER     => "TOKEN_STR_DELIMITER",
                            static::TOKEN_PARAMETERS_DELIMITER => "TOKEN_INPUT_DELIMITER",
                        };

                        if ($exception !== null) {
                            throw new \Exception(message: "Cannot start expression with {$exception}", code: 400);
                        }
                    }

                    if ($current === static::TOKEN_PARAMETERS_START && $isParameterStringParsingProcess === false && $isParametersParsingProcess === true) {
                        throw new \Exception("Unexpected TOKEN_INPUT_START at {$currentPosition}");
                    } else if ($current === static::TOKEN_PARAMETERS_END && $isParametersParsingProcess === false) {
                        throw new \Exception(message: "Unexpected TOKEN_INPUT_END at {$currentPosition}", code: 400);
                    }

                    if ($next === null) {
                        if ($isParameterStringParsingProcess === true) { // Parsing params sting
                            throw new \Exception(message: "Unexpected end of parameters while parsing string. String started at " . ($row + 1) . ":" . $stringStartPosition, code: 400);
                        }
                        if ($current !== static::TOKEN_PARAMETERS_END) { // Params list brace doesn't close
                            throw new \Exception(message: "Reached end of parameters string without parameter list at {$currentPosition} ", code: 400);
                        }
                    }

                    if ($current === static::TOKEN_STRING_ESCAPE) {
                        if ($isParameterStringParsingProcess && $next !== null) {
                            if (in_array($next, static::VALID_ESCAPE_CHARACTERS) === false) {
                                throw new \Exception(message: "Invalid string escape sequence at {$currentPosition}" . " (\"{$next}\")", code: 400);
                            }

                            if ($column + 2 >= $inputLength) {
                                throw new \Exception(message: "Unexpected end of parameters while parsing string. String started at " . ($row + 1) . ":" . $stringStartPosition, code: 400);
                            }

                            $currentPart .= $next;
                            $column++;
                            continue;
                        }
                    } else if ($current === static::TOKEN_STRING_DELIMITER) {
                        if ($isParameterStringParsingProcess === true) {
                            $isParameterStringParsingProcess = false;
                            $stringStartPosition = -1;

                            continue;
                        }

                        $isParameterStringParsingProcess = true;
                        $stringStartPosition = $column + 1;
                        $currentPart = '';

                        continue;
                    } else if ($current === static::TOKEN_PARAMETERS_END) {
                        if ($isParameterStringParsingProcess === true) {
                            $currentPart .= $current;
                            continue;
                        }
                        $instructionParameters[] = $currentPart;

                        foreach ($instructionParameters as &$instructionParameter) {
                            $instructionParameter = trim($instructionParameter);

                            if ($useTypeCast === true) {
                                if (is_numeric($instructionParameter) === true) {
                                    if ($instructionParameter == (float) $instructionParameter) {
                                        $instructionParameter = (float) $instructionParameter;
                                    } else if ($instructionParameter == (int) $instructionParameter) {
                                        $instructionParameter = (int) $instructionParameter;
                                    }
                                } else {
                                    $instructionParameter = match ($instructionParameter) {
                                        default => $instructionParameter,
                                        "null"  => null,
                                        "false" => false,
                                        "true"  => true,
                                    };
                                }
                            }
                        }
                        unset($instructionParameter);

                        $instructions[] = [
                            "operation"  => $operationName,
                            "parameters" => $instructionParameters,
                        ];

                        $currentPart = '';
                        $isParametersParsingProcess = false;
                        $inputListStartPosition = -1;
                        $operationName = '';
                        $instructionParameters = [];

                        continue;
                    } else if ($current === static::TOKEN_PARAMETERS_DELIMITER) {
                        if ($isParameterStringParsingProcess === true) {
                            $currentPart .= $current;
                            continue;
                        }

                        if ($isParametersParsingProcess === false) {
                            throw new \Exception(message: "Unexpected TOKEN_INPUT_DELIMITER outside of parameter list at {$currentPosition}", code: 400);
                        }

                        $instructionParameters[] = $currentPart;
                        $currentPart = '';

                        continue;
                    } else if ($current === static::TOKEN_PARAMETERS_START) {
                        if ($isParameterStringParsingProcess === true) {
                            $currentPart .= $current;
                            continue;
                        }

                        $currentPart = trim($currentPart);
                        if (\DSL\Operation\Available::operationNameDefined($currentPart) === false) {
                            throw new \Exception\DSL\Operation\Unknown("'{$currentPart}' at " . ($row + 1) . " line");
                        }
                        $operationName = $currentPart;

                        $currentPart = '';
                        $isParametersParsingProcess = true;
                        $inputListStartPosition = $column + 1;

                        continue;
                    }

                    $currentPart .= $current;
                }


            }
        } catch (\Exception $e) {
            echo "> FAIL: {$e->getMessage()}\n";
            exit();
            throw new \Exception\DSL\InstructionsFile\ParseFail(message: "Parse failed", previous: $e);
        }

        return $instructions;
    }
}
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
     * Check string is valid JSON-document
     *
     * @param string $string
     *
     * @return false|array
     */
    protected static function __stringIsJSON(string $string) : false|array {
        if (empty($string) === true) {
            return false;
        }

        $data = @json_decode($string, true);

        return is_array($data) === true ? $data : false;
    }

    /**
     * Parse code
     *
     * @param string $code        Code string
     * @param bool   $useTypeCast cast array, numeric(detect float only with non-zero value after point), boolean, null value or not
     *
     * @return array
     *
     * @throws \Exception\DSL\InstructionsFile\ParseFail
     */
    public static function parse(string $code, bool $useTypeCast = false) : array {
        $entityInstructions = [];

        try {
            $_instructions = array_map("trim", explode("\n", str_replace("\r", '', $code)));
            $previousEntityName = null;
            $currentEntityName = null;
            $instructions = [];
            for ($row = 0; $row < count($_instructions); $row++) {
                if (preg_match("/\A(#[a-zA-Z_0-9]{3,})\Z/", $_instructions[$row]) === 1) {
                    $previousEntityName = $currentEntityName;
                    $currentEntityName = str_replace("#", '', $_instructions[$row]);

                    if (\DSL\Entity\Pool::Instance()->isDefinedEntity($currentEntityName) === false) {
                        throw new \Exception\DSL\Operation\Unknown("unknown entity `{$currentEntityName}`");
                    }

                    if ($row !== 0) {
                        if (count($instructions) === 0) {
                            throw new \Exception(message: "Can't define new Entity '{$currentEntityName}' at " . ($row + 1) . " while not call some methods for previously Entity '{$previousEntityName}'", code: 409);
                        }

                        $entityInstructions[] = [
                            "entity"  => $previousEntityName,
                            "methods" => $instructions,
                        ];

                        $instructions = [];
                    }

                    continue;
                } else if ($row === 0) {
                    throw new \Exception(message: "Don't defined Entity");
                }


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
                                    if ($instructionParameter == (int) $instructionParameter) {
                                        $instructionParameter = (int) $instructionParameter;
                                    } else if ($instructionParameter == (float) $instructionParameter) {
                                        $instructionParameter = (float) $instructionParameter;
                                    }
                                } else if (($json = static::__stringIsJSON($instructionParameter)) !== false) {
                                    $instructionParameter = $json;
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
                            "name"       => $operationName,
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
                        // TODO: check method on exists on defined Entity
                        if (\DSL\Entity\Pool::Instance()->isDefinedEntityMethod($currentEntityName,$currentPart) === false) {
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
            if (count($instructions) === 0) {
                throw new \Exception(message: "Can't define new Entity '{$currentEntityName}' at " . ($row + 1) . " with no some method calls", code: 409);
            }

            $entityInstructions[] = [
                "entity"  => $currentEntityName,
                "methods" => $instructions,
            ];
        } catch (\Exception $e) {
            echo "> FAIL: {$e->getMessage()}\n";
            exit();
            throw new \Exception\DSL\InstructionsFile\ParseFail(message: "Parse failed", previous: $e);
        }

        var_export($entityInstructions);
        exit();

        return $entityInstructions;
    }
}

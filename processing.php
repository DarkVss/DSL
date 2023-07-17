<?php

const DS = DIRECTORY_SEPARATOR;
define("RUN_PATH", realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

require_once RUN_PATH . "vendor/autoload.php";

$instructionsFile = RUN_PATH . "instructionsExample.txt";

try {
    var_dump(
        \DSL::init()
            ->useTypeCast(true)
            ->parseFile($instructionsFile)
    );
} catch (\Exception\DSL $e) {
    echo "> Library exception: {$e->getMessage()}\n\t{$e->getFile()}::{$e->getLine()}\n";
} catch (\Exception $e) {
    echo "> System exception: {$e->getMessage()}\n\t{$e->getFile()}::{$e->getLine()}\n";
}

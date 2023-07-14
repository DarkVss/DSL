<?php

const DS = DIRECTORY_SEPARATOR;
define("RUN_PATH", realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

require_once RUN_PATH . "vendor/autoload.php";

$instructionsFile = RUN_PATH . "instructionsExample.txt";

var_dump(\DSL\EntityDescriptor::generate(\DSL\Entity\SimpleCalculator::class));

exit();
$reflector = new \ReflectionClass(\DSL\Entity\SimpleCalculator::class);
$methods = $reflector->getMethods(\ReflectionMethod::IS_PUBLIC);

for ($i = 0; $i < 3; $i++) {
    $method = $methods[$i];
    var_dump(
        $method->getName(),
        array_map(
            fn(\ReflectionAttribute $attribute) => $attribute->getName(),
            $method->getAttributes()
        )
    );

    $comment = $method->getDocComment() ?: null;
    if ($comment !== null) {
        $comment = explode("\n", $comment)[1] ?? '';
        if (str_contains($comment, "*") === true) {
            $comment = substr($comment, strpos($comment, "*") + 2);
            if ($comment[0] === "@") {
                $comment = null;
            }
        }
    }
    if (empty($comment) === true) {
        $comment = "### Method have no description ###";
    }
    var_dump($comment);
}
echo "\n\n--------------------\n\n";
foreach ($methods as $method) {
    if (preg_match("/\A" . \DSL\EntityDescriptor\Method::METHOD_NAME_BEGIN . "[a-zA-Z_0-9]+\z/", $method->getName()) === 1) {
        $userMethodName = substr($method->getName(), strlen(\DSL\EntityDescriptor\Method::METHOD_NAME_BEGIN));
        if ($method->isStatic() === false) {
            echo "Method name: `{$method->getName()}`(`{$userMethodName}`)\n";

            $comment = $method->getDocComment() ?: null;
            if ($comment !== null) {
                $comment = explode("\n", $comment)[1] ?? '';
                if (str_contains($comment, "*") === true) {
                    $comment = substr($comment, strpos($comment, "*") + 2);
                    if ($comment[0] === "@") {
                        $comment = null;
                    }
                }
            }
            if (empty($comment) === true) {
                $comment = "### Method have no description ###";
            }
            echo "Method comment: `{$comment}`\n";
            $params = $method->getParameters();
            if (count($params) === 0) {
                echo "Method have no params\n";
            } else {
                foreach ($params as $index => $param) {
                    echo "Parameter #" . ($index + 1) . ": \n";
                    echo "\tName: `{$param->name}`\n";
                    echo "\tType: `" . ($param->getType() ?? "null") . "`\n";
                    echo "\tOptional: `" . ($param->isOptional() === true ? "true" : "false") . "`\n";
                    echo "\tAllow null: `" . ($param->allowsNull() === true ? "true" : "false") . "`\n";
                    echo "-----------------------\n";
                }
            }
        }
    }
}

exit();


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

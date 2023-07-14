<?php

namespace DSL;

final class Helpers {
    /**
     * Extraction comment from PHPDoc string
     *
     * @param string $comment
     *
     * @return ?string NULL - have no comment, otherwise - parsed extracted comment
     */
    public static function extractDocComment(string $comment) : ?string {
        $data = null;

        if (empty($comment) === false) {
            $comment = explode("\n", $comment)[1] ?? '';
            if (str_contains($comment, "*") === true) {
                $data = substr($comment, strpos($comment, "*") + 2);
                if ($data[0] === "@") {
                    $data = null;
                }
            }
        }

        return $data;
    }

    public static function extractClassname(string $classname) : string { return substr(strrchr($classname, "\\") ?: $classname, 1); }

    public static function convertCamelCaseToSnakeCase(string $text) : string {
        return str_replace("__", "_", strtolower(preg_replace("/[A-Z]([A-Z](?![a-z]))*/", "_$0", lcfirst($text))));
    }

    public static function convertSnakeCaseToCamelCase(string $text) : string {
        return ucwords(str_replace("_", '', ucwords($text, "_")));
    }
}

<?php

namespace Main;

class Output
{
    public static function error(string $string)
    {
        echo "\033[31m$string \033[0m\n";
    }

    public static function success(string $string)
    {
        echo "\033[32m$string \033[0m\n";
    }

    public static function warning(string $string)
    {
        echo "\033[33m$string \033[0m\n";
    }

    public static function info(string $string)
    {
        echo "\033[36m$string \033[0m\n";
    }

    public static function default(string $string)
    {
        echo "{$string}\n";
    }
}

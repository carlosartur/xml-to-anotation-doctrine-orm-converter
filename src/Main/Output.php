<?php

namespace Main;

class Output
{
    /**
     * Show message in red, meaning error
     *
     * @param string $string
     * @return void
     */
    public static function error(string $string)
    {
        echo "\033[31m$string \033[0m\n";
    }

    /**
     * Show message in green, meaning success
     *
     * @param string $string
     * @return void
     */
    public static function success(string $string)
    {
        echo "\033[32m$string \033[0m\n";
    }

    /**
     * Show message in yellow, meaning caution
     *
     * @param string $string
     * @return void
     */
    public static function warning(string $string)
    {
        echo "\033[33m$string \033[0m\n";
    }

    /**
     * Show message in blue, meaning some relevant info
     *
     * @param string $string
     * @return void
     */
    public static function info(string $string)
    {
        echo "\033[36m$string \033[0m\n";
    }


    /**
     * Show message in default color of terminal
     *
     * @param string $string
     * @return void
     */
    public static function default(string $string)
    {
        echo "{$string}\n";
    }
}

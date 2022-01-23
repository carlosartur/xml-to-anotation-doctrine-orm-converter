<?php
abstract class AbstractSingleton
{
    private static $instance;

    protected function __construct()
    {
    }

    final protected function __clone()
    {
    }

    final protected function __wakeup()
    {
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }
}

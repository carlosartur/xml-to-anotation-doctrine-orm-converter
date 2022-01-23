<?php

namespace Main;

use Exception;

abstract class AbstractSingleton
{
    /** @var static $instance - THE instance of this class */
    protected static $instance;

    protected function __construct()
    {
    }

    final public function __clone()
    {
        throw new Exception("Singleton objects must not be cloned");
    }

    final public function __wakeup()
    {
        throw new Exception("Singleton objects must not be unserialized");
    }

    /**
     * Return the only instance of the object
     *
     * @return self
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}

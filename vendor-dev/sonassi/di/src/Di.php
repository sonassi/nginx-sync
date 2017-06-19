<?php namespace Sonassi\Di;

class Di extends Object
{

    private static $container;
    private static $containerBuilder;

    private static function container()
    {
        if (!self::$container) {
            self::$containerBuilder = new \DI\ContainerBuilder;
            self::$container = self::$containerBuilder->build();
        }
        return self::$container;
    }

    public static function init()
    {
        self::container();
    }

    public static function make($class, $args = [])
    {
        return self::container()->make($class, $args);
    }

    public static function get($class = false, $args = [])
    {
        if (!$class)
            $class = get_called_class();

        return self::container()->get($class, $args);
    }

    public static function call($data, $args = [])
    {
        return self::container()->call($data, $args);
    }

}
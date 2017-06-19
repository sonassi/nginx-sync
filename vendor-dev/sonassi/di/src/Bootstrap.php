<?php namespace Sonassi\Di;

class Bootstrap extends Object
{
    private static $instance;

    public function __construct()
    {
        $this->di = self::di(func_get_args());
    }

    public function init()
    {

    }

    public static function run()
    {
        self::$instance = Di::get('\Sonassi\Di\Bootstrap')->init();
    }

  }

<?php

namespace Radius;

class Autoloader
{
    static public function register()
    {
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(array(new self, 'autoload'));
    }

    static public function autoload($class)
    {
        if (0 !== strpos($class, 'Radius')) {
            return;
        }

        require __DIR__.'/../'.str_replace('\\', '/', $class).'.php';

        return true;
    }
}

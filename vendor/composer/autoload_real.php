<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit859286a6c2f540ddbd409e72c23a4ed6
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit859286a6c2f540ddbd409e72c23a4ed6', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit859286a6c2f540ddbd409e72c23a4ed6', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit859286a6c2f540ddbd409e72c23a4ed6::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}

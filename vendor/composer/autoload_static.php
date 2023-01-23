<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite4289d82e4fcf27924b83d1a672d6e62
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Sandro_Nunes\\Lib\\' => 17,
            'Sandro_Nunes\\Ajax_Taxonomy_Filter\\' => 34,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Sandro_Nunes\\Lib\\' => 
        array (
            0 => __DIR__ . '/../..' . '/lib',
        ),
        'Sandro_Nunes\\Ajax_Taxonomy_Filter\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite4289d82e4fcf27924b83d1a672d6e62::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite4289d82e4fcf27924b83d1a672d6e62::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInite4289d82e4fcf27924b83d1a672d6e62::$classMap;

        }, null, ClassLoader::class);
    }
}

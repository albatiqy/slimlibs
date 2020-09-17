<?php declare (strict_types = 1);

return static function ($settings) {
    $providers = require LIBS_DIR . '/requires/providers.php';

    $providers += $settings['container_implements'];

    $container = Albatiqy\Slimlibs\Container\Container::getInstance($providers);
    $container->set('settings', $settings);
    $container->registerFunctions(require LIBS_DIR . '/requires/functions.php');
    $container->mapAlias(Psr\Log\LoggerInterface::class, 'monolog');
    /*
    $container->defineExtends(Albatiqy\Slimlibs\Providers\Database\DbService::class, function($subclass){
        return $subclass::getInstance();
    });
    */
    return $container;
};

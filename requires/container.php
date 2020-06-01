<?php declare (strict_types = 1);

return static function ($settings) {
    $providers = require LIBS_DIR . '/requires/providers.php';

    $providers[Albatiqy\Slimlibs\Providers\Auth\AuthInterface::class] = $settings['auth_provider'];

    $container = Albatiqy\Slimlibs\Container\Container::getInstance($providers);
    $container->set('settings', $settings);
    $container->mapAlias(Psr\Log\LoggerInterface::class, 'monolog');
    $container->setFunction('getBaseUrl', function($container, $https=false){
        $request = $container->get('request');
        $uri = $request->getUri();
        return  ($https?'https':$uri->getScheme()) . '://' . $uri->getHost().\BASE_PATH;
    });
    /*
    $container->defineExtends(Albatiqy\Slimlibs\Providers\Database\DbService::class, function($subclass){
        return $subclass::getInstance();
    });
    */
    return $container;
};

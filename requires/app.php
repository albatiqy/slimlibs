<?php declare (strict_types = 1);

use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Albatiqy\Slimlibs\Error\ErrorHandler;
use Slim\App;

return static function($container) use ($settings) {
    $serverRequestCreator = ServerRequestCreatorFactory::create();
    $request = $serverRequestCreator->createServerRequestFromGlobals();

    AppFactory::setContainer($container);

    $app = AppFactory::create();

    $container
        ->set(App::class, $app)
        ->set('request', $request);

    $errorSettings = $settings['error_handler_middleware'];

    $errorMiddleware = $app->addErrorMiddleware($errorSettings['display_error_details'], $errorSettings['log_errors'], $errorSettings['log_error_details']);

    $callableResolver = $app->getCallableResolver();
    $responseFactory = $app->getResponseFactory();
    $errorHandler = new ErrorHandler($callableResolver, $responseFactory, $container);
    $errorMiddleware->setDefaultErrorHandler($errorHandler);

    $cacheSettings = $settings['cache'];

    if ($cacheSettings['routes'] ?? false) {
        $routeCollector = $app->getRouteCollector();
        $routeCollector->setCacheFile($cacheSettings['base_dir'] . '/routes.php');
    }

    return $app;
};
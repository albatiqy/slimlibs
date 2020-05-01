<?php declare (strict_types = 1);

error_reporting(E_ALL);
//ini_set('error_reporting', "On");
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Jakarta');

$settings = require LIBS_DIR . '/requires/settings.php';
//slimlibs_monolog_errors($settings['monolog']);
//ini_set('error_log', $settings['log_dir'].'/php-error.log'); //cause empty logs

$container = (require LIBS_DIR . '/requires/container.php')($settings);
set_error_handler($container->get('php_error_handler'));

$app = (require LIBS_DIR . '/requires/app.php')($container);
$app->setBasePath(BASE_PATH);
//$app->getRouteCollector()->setDefaultInvocationStrategy(new Slim\Handlers\Strategies\RequestHandler(true));
$request = $container->get('request');

(require LIBS_DIR . '/web/route.php')($app);
(require APP_DIR . '/config/middleware.php')($app);

$app->run($request);
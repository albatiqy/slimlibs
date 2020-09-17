<?php declare (strict_types = 1);

$settings = [
    'tmp_dir' => APP_DIR . '/var/tmp',
    'log_dir' => APP_DIR . '/var/log',
    'backend_path' => '/admin',
    'login_path' => '/login',
    'cache'=>[
        'base_dir' => APP_DIR . '/var/cache',
        'pages' => true,
        'routes' => true
    ],
    'container_implements'=> [
        Albatiqy\Slimlibs\Providers\Auth\AuthInterface::class => App\Providers\Auth\Db::class
    ],
    // Error Handling Middleware settings
    'error_handler_middleware' => [

        // Should be set to false in production
        'display_error_details' => false,

        // Parameter is passed to the default ErrorHandler
        // View in rendered output by enabling the "displayErrorDetails" setting.
        // For the console and unit tests we also disable it
        'log_errors' => true,

        // Display error details in error log
        'log_error_details' => true,
    ],
    'monolog' => [
        'name' => 'slim-app',
        'path_error' => APP_DIR . '/var/log/error.log',
        'path_app' => APP_DIR . '/var/log/app.log',
        'level' => Monolog\Logger::DEBUG,
        'line_formatter' => "[%datetime%] %message%\n"
    ],
    'jwt' => [
        'secret' => 'mBC5v1sOKVvbdEitdSBenu59nfNfhwkedkJVNabosTw=',
        'algo' => 'HS256',
        'max_age' => (60*60), //(60*60)
        'leeway' => 10,
        'aud' => 'http://site.com',
        'iss'    => 'http://api.mysite.com'
    ],
    'telegram_bot' => [
        'token' => '',
        'channelName' => ''
    ]
];
$settings = array_replace_recursive($settings, require APP_DIR . '/config/settings.php');
if (APP_ENV == 'DEV') {
    $settings = array_replace_recursive($settings, require (LIBS_DIR . '/requires/development.php'));
}

return $settings;
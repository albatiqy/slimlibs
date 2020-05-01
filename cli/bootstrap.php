<?php declare (strict_types = 1);

if (isset($_SERVER['REQUEST_METHOD'])) {
    echo "Only CLI allowed. Script stopped.\n";
    exit(1);
}

$console = PHP_SAPI == 'cli' ? true : false;

if (!$console) {
    exit(1);
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Jakarta');

if (!is_array($argv)) {
    if (!@is_array($_SERVER['argv'])) {
        if (!@is_array($GLOBALS['HTTP_SERVER_VARS']['argv'])) {
            echo "Could not read cmd args (register_argc_argv=Off?)";
            exit(1);
        }
        return $GLOBALS['HTTP_SERVER_VARS']['argv'];
    }
    return $_SERVER['argv'];
}
array_shift($argv);

$settings = require LIBS_DIR . '/requires/settings.php';
//slimlibs_monolog_errors($settings['monolog']);
ini_set('error_log', $settings['log_dir'] . '/php-error.log');
$container = (require LIBS_DIR . '/requires/container.php')($settings);
$app = (require LIBS_DIR . '/requires/app.php')($container);

$cli = new Albatiqy\Slimlibs\Command\Cli($argv);
$cli->run($container);

/*
$iterate = function ($base_dir) {
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir), RecursiveIteratorIterator::SELF_FIRST);
foreach ($iterator as $fileinfo) {
if ($fileinfo->isFile()) {
$path = $fileinfo->getPath();
$filename = $fileinfo->getBasename();
$cli->info($filename);
}
}
};
$iterate(LIBS_DIR . '/cli/commands');
 */

//$response = $app->handle($request);
//$responseEmitter = new Slim\ResponseEmitter();
//$responseEmitter->emit($response);

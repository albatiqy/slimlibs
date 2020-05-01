<?php

define('LIBS_DIR', realpath(__DIR__));

function slimlibs_get_error_monolog($settings) {
    $logger = new Monolog\Logger('errors');
    $processor = new Monolog\Processor\UidProcessor();
    $logger->pushProcessor($processor);
    $handler = new Monolog\Handler\RotatingFileHandler($settings['path_error'], 0, $settings['level'], true, 0664);
    $handler->setFilenameFormat('{date}-{filename}', 'Y/m/d');
    $handler->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true, true));
    $logger->pushHandler($handler);
    return $logger;
}

function slimlibs_monolog_errors($settings) {
    $errorHandler = new Monolog\ErrorHandler(slimlibs_get_error_monolog($settings));
    $errorHandler->registerErrorHandler([], true);
    $errorHandler->registerFatalHandler();
}
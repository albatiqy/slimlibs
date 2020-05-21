<?php declare (strict_types = 1);

return [
    'db' => function($container) use ($settings) {
        $settings = $settings['db'];
        $db = Albatiqy\Slimlibs\Providers\Database\DbProxy::getInstance($settings);
        return $db;
    },
    'jwt' => function($container) use ($settings) {
        $settings = $settings['jwt'];
        $jwt = new Albatiqy\Slimlibs\Providers\Jwt\JWT($settings['secret'], $settings['algo'], $settings['max_age'], $settings['leeway']);
        return $jwt;
    },
    Psr\Log\LoggerInterface::class => function($container) use ($settings) {
        $settings = $settings['monolog'];
        $logger = new Monolog\Logger($settings['name']);
        $processor = new Monolog\Processor\UidProcessor();
        $logger->pushProcessor($processor);
        $handler = new Monolog\Handler\RotatingFileHandler($settings['path_app'], 0, $settings['level'], true, 0664);
        //$handler = new Monolog\Handler\StreamHandler($settings['path'], $settings['level']);
        $handler->setFilenameFormat('{date}-{filename}', 'Y/m/d');
        $handler->setFormatter(new Monolog\Formatter\LineFormatter($settings['line_formatter'],
            null, // Datetime format
            true, // allowInlineLineBreaks option, default false
            true  // ignoreEmptyContextAndExtra option, default false
        ));
        $logger->pushHandler($handler);
        return $logger;
    },
    'renderer' => function($container) {
        $renderer = new Albatiqy\Slimlibs\Providers\Renderer\Engine(APP_DIR . '/view/templates');
        $renderer->addFolder('libs', LIBS_DIR . '/web/templates');
        $renderer->addData([
            'container' => $container
        ]); //global
        //$renderer->loadExtension(new Albatiqy\Slimlibs\Providers\Renderer\Extension\Slimlibs($container));
        return $renderer;
    },
    'validator' => function($container) {
        $messages = require APP_DIR . '/config/validation.php';
        return function($form, $labels = null, $options = []) use ($container, $messages) {
            return new Albatiqy\Slimlibs\Providers\Validation\Validator($form, $options, $messages, $labels);
        };
    },
    'php_error_handler' => function ($container) use ($settings) {
        $errorno_fn = function ($type) {
            switch($type)
            {
                case E_ERROR: // 1 //
                    return 'E_ERROR';
                case E_WARNING: // 2 //
                    return 'E_WARNING';
                case E_PARSE: // 4 //
                    return 'E_PARSE';
                case E_NOTICE: // 8 //
                    return 'E_NOTICE';
                case E_CORE_ERROR: // 16 //
                    return 'E_CORE_ERROR';
                case E_CORE_WARNING: // 32 //
                    return 'E_CORE_WARNING';
                case E_COMPILE_ERROR: // 64 //
                    return 'E_COMPILE_ERROR';
                case E_COMPILE_WARNING: // 128 //
                    return 'E_COMPILE_WARNING';
                case E_USER_ERROR: // 256 //
                    return 'E_USER_ERROR';
                case E_USER_WARNING: // 512 //
                    return 'E_USER_WARNING';
                case E_USER_NOTICE: // 1024 //
                    return 'E_USER_NOTICE';
                case E_STRICT: // 2048 //
                    return 'E_STRICT';
                case E_RECOVERABLE_ERROR: // 4096 //
                    return 'E_RECOVERABLE_ERROR';
                case E_DEPRECATED: // 8192 //
                    return 'E_DEPRECATED';
                case E_USER_DEPRECATED: // 16384 //
                    return 'E_USER_DEPRECATED';
            }
            return "";
        };
        $write_fn = function($errno, $errstr, $errfile, $errline) use ($container, $settings, $errorno_fn) {
            $format = '['.date('d/m/Y H:i:s').'] '. $errorno_fn($errno).': '.$errstr.' at '.$errfile.' ('.$errline.")\r\n";
            $telegram = $container->get(Albatiqy\Slimlibs\Providers\Libs\TelegramBot::class);
            $telegram->messageUserText('albatiqy', $format);
            //file_put_contents($settings['log_dir'].'/php-error.log', $format, FILE_APPEND);
        };
        $last_error = error_get_last();
        if (is_array($last_error)) {
            $write_fn($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
        }
        error_clear_last();
        return function($errno, $errstr, $errfile, $errline) use ($write_fn) {
            $write_fn($errno, $errstr, $errfile, $errline);
        };
    }
];
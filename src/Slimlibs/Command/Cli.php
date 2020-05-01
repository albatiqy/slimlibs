<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

final class Cli {

    private $bin;
    private $options;
    private $colors;

    private $loglevel = [
        'debug' => ['', Colors::C_RESET, \STDOUT],
        'info' => ['ℹ ', Colors::C_CYAN, \STDOUT],
        'notice' => ['☛ ', Colors::C_CYAN, \STDOUT],
        'success' => ['✓ ', Colors::C_GREEN, \STDOUT],
        'warning' => ['⚠ ', Colors::C_BROWN, \STDERR],
        'error' => ['✗ ', Colors::C_RED, \STDERR],
        'critical' => ['☠ ', Colors::C_LIGHTRED, \STDERR],
        'alert' => ['✖ ', Colors::C_LIGHTRED, \STDERR],
        'emergency' => ['✘ ', Colors::C_LIGHTRED, \STDERR],
    ];

    private $logdefault = 'info';

    public function __construct($argv, $autocatch = true) {
        if ($autocatch) {
            \set_exception_handler([$this, 'fatal']);
        }

        if (count($argv) == 0) {
            \array_push($argv, 'help');
        } else {
            if (\substr($argv[0], 0, 1) == '-') {
                \array_unshift($argv, 'help');
            }
        }

        $this->colors = new Colors();
        $this->options = new Options($this->colors, $argv);
    }

    public function run($container) {
        //setup
        $dir = '';
        $bin = $this->options->getBin();
        $fileload = \APP_DIR . '/var/commands/' . $bin . '.php';
        if (!\file_exists($fileload)) {
            $fileload = \LIBS_DIR . '/cli/commands/' . $bin . '.php';
            if (!\file_exists($fileload)) {
                $this->error('perintah tidak ditemukan');
                exit;
            }
        }
        $cmdmanifest = require $fileload;
        $class = $cmdmanifest['handler'];
        $class = new \ReflectionClass($class); // chk abstract command?
        $result = $cmdmanifest['options'];

        $this->options->setHelp($result['help']);
        foreach ($result['opts'] as $key => $flag) {
            $this->options->registerOption($key, $flag['help'], $flag['short'], $flag['arg']);
        }
        foreach ($result['args'] as $param) {
            $this->options->registerArgument($param['arg'], $param['help'], $param['required']);
        }
        foreach ($result['commands'] as $key => $command) {
            $this->options->registerCommand($key, $command['help']);
            foreach ($command['opts'] as $key1 => $flag1) {
                $this->options->registerOption($key1, $flag1['help'], $flag1['short'], $flag1['arg'], $key);
            }
            foreach ($command['args'] as $param1) {
                $this->options->registerArgument($param1['arg'], $param1['help'], $param1['required'], $key);
            }
        }

        $this->registerDefaultOptions();
        $this->parseOptions();
        $this->handleDefaultOptions();
        $this->setupLogging();
        $this->checkArgments();

        // main

        $instance = $class->newInstance($this, $container);
        $arguments = $this->options->getArgs();
        $instance->setArguments($arguments);
        $cmd = $this->options->getCmd();
        $opts = $this->options->getOpt();
        if ($cmd) {
            $cmdinfo = $result['commands'][$cmd];
            $params = [];
            foreach ($opts as $key => $val) {
                $params[$cmdinfo['opts'][$key]['name']] = $val;
            }
            $method = $class->getMethod($cmdinfo['name']);
            $args = \array_map(function ($method) use ($params) {
                if (\array_key_exists($method->getName(), $params)) {
                    return $params[$method->getName()];
                } else {
                    if ($method->isDefaultValueAvailable()) {
                        return $method->getDefaultValue();
                    }
                    return null;
                }
            }, $method->getParameters());

            $method->invokeArgs($instance, $args);
        } else {
            foreach ($opts as $key => $val) {
                if (\array_key_exists($key, $result['opts'])) {
                    $instance->{$result['opts'][$key]['name']} = $val;
                }
            }
            $instance->main();
        }
        /*
        $this->info('cmd:');
        print_r($this->options->getCmd());
        $this->info('opts:');
        print_r($this->options->getOpt());
        $this->info('args:');
        print_r($this->options->getArgs());
         */
        exit(0);
    }

    public function getHelp() {
        return $this->options->help();
    }

    public function colorText($text, $color) {
        return $this->colors->wrap($text, $color);
    }

    private function registerDefaultOptions() {
        $this->options->registerOption(
            'help',
            'Display this help screen and exit immediately.',
            'h'
        );
        $this->options->registerOption(
            'no-colors',
            'Do not use any colors in output. Useful when piping output to other tools or files.'
        );
        $this->options->registerOption(
            'loglevel',
            'Minimum level of messages to display. Default is ' . $this->colors->wrap($this->logdefault, Colors::C_CYAN) . '. ' .
            'Valid levels are: debug, info, notice, success, warning, error, critical, alert, emergency.',
            null,
            'level'
        );
    }

    private function handleDefaultOptions() {
        if ($this->options->getOpt('no-colors')) {
            $this->colors->disable();
        }
        if ($this->options->getOpt('help')) {
            echo $this->options->help();
            exit(0);
        }
    }

    private function setupLogging() {
        $level = $this->options->getOpt('loglevel', $this->logdefault);
        if (!isset($this->loglevel[$level])) {
            $this->fatal('Unknown log level');
        }

        foreach (\array_keys($this->loglevel) as $l) {
            if ($l == $level) {
                break;
            }

            unset($this->loglevel[$l]);
        }
    }

    private function parseOptions() {
        $this->options->parseOptions();
    }

    private function checkArgments() {
        $this->options->checkArguments();
    }

    public function fatal($error, array $context = []) {
        $code = 0;
        if (\is_object($error) && ($error instanceof CommandException)) {
            $this->debug(\get_class($error) . ' caught in ' . $error->getFile() . ':' . $error->getLine());
            $this->debug($error->getTraceAsString());
            $code = $error->getCode();
            $error = $error->getMessage();
        }
        if (!$code) {
            $code = CommandException::E_ANY;
        }

        $this->critical($error, $context);
        if ($code == CommandException::E_OPT_ARG_REQUIRED) {
            echo "\n";
            echo $this->getHelp();
        }
        exit($code);
    }

    public function emergency($message, array $context = []) {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = []) {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = []) {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = []) {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = []) {
        $this->log('warning', $message, $context);
    }

    public function success($string, array $context = []) {
        $this->log('success', $string, $context);
    }

    public function notice($message, array $context = []) {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = []) {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = []) {
        $this->log('debug', $message, $context);
    }

    private function log($level, $message, array $context = []) {
        if (!isset($this->loglevel[$level])) {
            return;
        }

        list($prefix, $color, $channel) = $this->loglevel[$level];
        if (!$this->colors->isEnabled()) {
            $prefix = '';
        }

        $message = $this->interpolate($message, $context);
        $this->colors->ptln($prefix . $message, $color, $channel);
    }

    private function interpolate($message, array $context = []) {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!\is_array($val) && (!\is_object($val) || \method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string)$val;
            }
        }

        return \strtr((string)$message, $replace);
    }
}

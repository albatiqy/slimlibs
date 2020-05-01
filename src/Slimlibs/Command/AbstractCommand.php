<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

abstract class AbstractCommand {

    protected $cli;
    protected $args;
    protected $container;

    abstract public function main();

    public function __construct($cli, $container) {
        $this->cli = $cli;
        $this->container = $container;
    }

    public function setArguments($args) {
        $this->args = $args;
    }

    protected function writeLine($text) {
        echo $text . "\n";
    }

    protected function showHelp() {
        echo $this->cli->getHelp();
        exit;
    }

    protected function emergency($message, array $context = []) {
        $this->cli->emergency($message, $context);
    }

    protected function alert($message, array $context = []) {
        $this->cli->alert($message, $context);
    }

    protected function critical($message, array $context = []) {
        $this->cli->critical($message, $context);
    }

    protected function error($message, array $context = []) {
        $this->cli->error($message, $context);
    }

    protected function warning($message, array $context = []) {
        $this->cli->warning($message, $context);
    }

    protected function success($string, array $context = []) {
        $this->cli->success($string, $context);
    }

    protected function notice($message, array $context = []) {
        $this->cli->notice($message, $context);
    }

    protected function info($message, array $context = []) {
        $this->cli->info($message, $context);
    }

    protected function debug($message, array $context = []) {
        $this->cli->debug($message, $context);
    }

    protected function color($text, $color) {
        return $this->cli->colorText($text, $color);
    }
}
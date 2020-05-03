<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

abstract class AbstractTelegramCommand {

    protected const MAP = 'undefined';

    protected $container;

    abstract public function run($message, $bot);

    public function __construct($container) {
        $this->container = $container;
    }
}
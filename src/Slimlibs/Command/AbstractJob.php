<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

use Closure;

abstract class AbstractJob {

    private $log = '';
    private $output = '';
    private $lockFile = '';

    protected const MAP = 'undefined';

    protected $container;

    abstract protected function handle();

    protected function locking(Closure $fn) {
        if (\file_exists($this->lockFile)) {
            return false;
        }
        \file_put_contents($this->lockFile, 'inuse');
        $result = $fn();
        \unlink($this->lockFile);
        return $result;
    }

    protected function log($msg) {
        $this->log .= "\r\n\r\n".$msg;
    }

    public function getLogs() {
        return $this->log;
    }

    public function getOutput() {
        return $this->output;
    }

    public function run() {
        $console = \PHP_SAPI == 'cli' ? true : false;
        if (!$console) {
            throw new \Exception('job harus dijalankan dalam mode cli');
        }
        \ob_start();
        $result = false;
        try {
            $result = $this->handle();
        } catch (\Exception $e) {
            if (\file_exists($this->lockFile)) {
                \unlink($this->lockFile);
            }
            throw $e;
        }
        $this->output = \ob_get_contents();
        \ob_end_clean();
        return $result;
    }

    public function __construct($container) {
        $this->container = $container;
        $this->lockFile = \APP_DIR . '/var/tmp/'. \substr(\strrchr(\get_class($this), "\\"), 1).'.jobstate';
    }

    public function getMapName() {
        return static::MAP;
    }
}
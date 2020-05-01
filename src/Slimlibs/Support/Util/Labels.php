<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Util;

final class Labels {

    private static $instances = [];
    private static $config = null;
    private $entity = null;
    private $group = false;

    private function __construct($entity = null) {
        $this->entity = $entity;
        $this->group = \is_array(self::$config[$entity] ?? false);
    }

    public static function getInstance($entity = 'global') {
        if (!isset(self::$instances[$entity])) {
            if (self::$config==null) {
                self::$config = require \APP_DIR . '/config/labels.php';
            }
            self::$instances[$entity] = new self($entity);
        }
        return self::$instances[$entity];
    }

    public function __get($key) {
        if ($this->group) {
            return self::$config[$this->entity][$key] ?? self::$config[$key] ?? $key;
        } else {
            return self::$config[$key] ?? $key;
        }
        return $key;
    }
}
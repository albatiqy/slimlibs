<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Cache;

use Albatiqy\Slimlibs\Support\Helper\CodeOut;
use Albatiqy\Slimlibs\Container\Container;

final class ObjectCache {

    private static $instances = [];

    private $values = [];
    private $file;
    private $object;
    private $expires;
    private $update = false;

    public static function getInstance($object, $expires=null) {
        $id = $object->cacheGetId();
        if (!isset(self::$instances[$id])) {
            self::$instances[$id] = new self($object, $id, $expires, Container::getInstance());
        }
        return self::$instances[$id];
    }

    private function __construct($object, $id, $expires, $container) {
        $cache_dir = $container->get('settings')['cache']['base_dir'];
        $this->file = $cache_dir . "/object-{$id}.php";
        if (\file_exists($this->file)) {
            $caches = require $this->file;
            if ((\time()-$caches['expires']) < $caches['generated']) {
                $this->values = $caches['objects'];
            } else {
                $this->values = $object->cacheGetValues();
                $this->update = true;
            }
        } else {
            $this->values = $object->cacheGetValues();
            $this->update = true;
        }
        $this->object = $object;
        if ($this->expires==null) {
            $this->expires = "(60 * 60 * 24 * 2)";
        }
        \register_shutdown_function([$this, 'saveCache']);
    }

    public function get($key) {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }
        $value = $this->object->cacheRetrieve($key);
        $this->update = true;
        $this->values[$key] = $value;
        return $value;
    }

    public function set($key, $value) {
        $this->values[$key] = $value;
        $this->update = true;
    }

    public function saveCache() {
        if ($this->update) {
            $fileout = "<?php\nreturn [\n    \"generated\" => " . \time() . ",\n    \"expires\" => ".$this->expires.", // (60detik * 60menit * 24jam * 2hari)\n    \"objects\" => " . CodeOut::fromArray($this->values) . "\n];";
            \file_put_contents($this->file, $fileout);
        }
    }
}
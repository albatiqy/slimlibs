<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs;

use Psr\Container\ContainerInterface;
use Albatiqy\Slimlibs\Support\Cache\ObjectCache;
use Albatiqy\Slimlibs\Support\Cache\UseObjectCacheInterface;

final class Configs implements UseObjectCacheInterface {

    private $container;
    private $db = null;
    private $cache;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->cache = ObjectCache::getInstance($this);
    }

    private function db() {
        if ($this->db==null) {
            $this->db = $this->container->get('db')();
        }
        return $this->db;
    }

    public function set($key, $value) {
        $validator = $this->getValidator();
        if ($validator->validate(['k'=>$key, 'v'=>$value])) {
            $db = $this->db();
            $sql = "INSERT INTO sys_configs (k,v) VALUES (:k,:v) ON DUPLICATE KEY UPDATE v=:v2";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':k' => $key,
                ':v' => $value,
                ':v2' => $value
            ]);
            $this->cache->set($key, $value);
            return true;
        }
        return false;
    }

    public function get($key) {
        return $this->cache->get($key);
    }

    public function delete($key) {
        $db = $this->db();
        $sql = "DELETE FROM sys_configs WHERE k=:k";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':k' => $key
        ]);
    }

    public function saveKeys($kv) {
        $validator = $this->getValidator();
        foreach ($kv as $key=>$itm) {
            if (!$validator->validate(['k'=>$key, 'v'=>$itm])) {
                \file_put_contents(\APP_DIR . '/var/tmp/ze', $key.' '.$itm);
                return false;
            }
        }
        $db = $this->db();
        try {
            $db->beginTransaction();
            $sql = "INSERT INTO sys_configs (k,v) VALUES (:k,:v) ON DUPLICATE KEY UPDATE v=:v2";
            $stmt = $db->prepare($sql);
            foreach ($kv as $key=>$itm) {
                $stmt->execute([':k' => $key, ':v' => $itm, ':v2' => $itm]);
                $this->cache->set($key, $itm);
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
        }
        return false;
    }

    private function getValidator() {
        return $this->container->get('validator')([
            'k' => ['regexp' => '/^[\w.]{5,}$/'],
            'v' => ['required']
        ]);
    }

    public function cacheRetrieve($key) {
        return null;
    }

    public function cacheGetId() {
        return 'configs';
    }

    public function cacheGetValues() { //[]
        $db = $this->db();
        $sql = "SELECT a.* FROM sys_configs a";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $values = [];
        foreach ($rows as $row) {
            $values[$row->k] = $row->v;
        }
        return $values;
    }

    private function log($error) {
        $logger = $this->container->get('monolog');
        $logger->info($error);
    }
}
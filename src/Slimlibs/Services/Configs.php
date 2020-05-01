<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Services;

use Albatiqy\Slimlibs\Providers\Database\MySqlDbService;
use Albatiqy\Slimlibs\Support\Cache\ObjectCache;
use Albatiqy\Slimlibs\Support\Cache\UseObjectCacheInterface;

final class Configs extends MySqlDbService implements UseObjectCacheInterface {

    protected const TABLE_NAME = 'sys_configs a';
    protected const PRIMARY_KEY = 'id';
    protected const ENTITY_NAME = 'config';
    protected const COLUMN_DEFS = [
        'id' => ['db' => 'a.id', 'type' => self::TYPE_NUMERIC],
        'k' => ['db' => 'a.k', 'type' => self::TYPE_STRING],
        'v' => ['db' => 'a.v', 'type' => self::TYPE_STRING],
    ];

    private $cache;

    protected function initialize() {
        $this->cache = ObjectCache::getInstance($this);
    }

    protected function insert($db, $data) {
        $this->cache->set($data['k'], $data['v']);
        return parent::insert($db, $data);
    }

    public function update($data, $id, $pkUpd = '') {
        //$this->cache->set($data['k'], $data['v']);
        return parent::update($data, $id, $pkUpd);
    }

    public function delete($id) {
        return parent::delete($id);
    }

    protected function getRules($opr) {
        $db = $this->db();
        $unique_key_rule = function ($val, $validator) use ($db, $opr) {
            $validator->setErrorMessage('unique_key', ':field sudah terpakai');
            $stmt = null;
            if ($opr == self::RULE_CREATE) {
                $stmt = $db->prepare(
                    'select a.* from sys_configs a where a.k=:k'
                );
                $stmt->execute([
                    ':k' => $val,
                ]);
            } elseif ($opr == self::RULE_UPDATE) {
                $stmt = $db->prepare(
                    'select a.* from sys_configs a where a.k=:k and a.id<>:id'
                );
                $stmt->execute([
                    ':k' => $val,
                    ':id' => $validator->getValue('id'),
                ]);
            }
            $row = $stmt->fetch();
            return !\is_object($row);
        };
        $rules = [
            'k' => ['required', 'alpha_num', 'min_length' => 5, 'unique_key' => $unique_key_rule],
            'v' => ['required'],
        ];
        return $rules;
    }

    public function get($key) {
        return $this->cache->get($key);
    }

    public function saveKeys($kv) { //==========================????transaction======
        $db = $this->db();
        $table = self::tableX();
        return $this->transaction($db, function($db) use ($kv, $table) {
            $sql = "INSERT INTO $table (k,v) VALUES (:k,:v) ON DUPLICATE KEY UPDATE v=:v2";
            $stmt = $db->prepare($sql);
            foreach ($kv as $key=>$itm) {
                $stmt->execute([':k' => $key, ':v' => $itm, ':v2' => $itm]);
                $this->cache->set($key, $itm);
            }
        });
    }

    public function cacheRetrieve($key) {
        return null;
    }

    public function cacheGetId() {
        return 'da_configs';
    }

    public function cacheGetValues() { //[]
        $db = $this->db();
        $table = static::TABLE_NAME;
        $sql = "SELECT a.* FROM $table";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $values = [];
        foreach ($rows as $row) {
            $values[$row->k] = $row->v;
        }
        return $values;
    }
}
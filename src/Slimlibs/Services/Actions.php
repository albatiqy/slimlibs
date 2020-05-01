<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Services;

use Albatiqy\Slimlibs\Providers\Database\MySqlDbService;

final class Actions extends MySqlDbService {

    protected const TABLE_NAME = 'sys_actions a';
    protected const PRIMARY_KEY = 'id';
    protected const ENTITY_NAME = 'action';
    protected const COLUMN_DEFS = [
        'id' => ['db' => 'a.id', 'type' => self::TYPE_NUMERIC],
        'class' => ['db' => 'a.class', 'type' => self::TYPE_STRING]
    ];

    public function rebuild($actions) {
        $db = $this->db();
        $table = self::tableX();
        return $this->transaction($db, function($db) use ($table, $actions) {
            $affected = $db->exec("TRUNCATE TABLE $table");
            $sql = "INSERT INTO $table (class, block) VALUES (:class, :block)";
            $stmt = $db->prepare($sql);
            foreach ($actions as $action=>$block) {
                $stmt->execute([':class' => $action, ':block' => $block]);
            }
        });
    }

/*
    public function remains() {
        $db = $this->db();
        $table = self::TABLE_NAME;
        $sql = "select a.* from $table where a.state=0 order by a.id limit 0,5";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
*/
}
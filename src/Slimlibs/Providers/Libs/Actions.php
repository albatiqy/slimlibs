<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs;

use Psr\Container\ContainerInterface;
use Albatiqy\Slimlibs\Providers\Auth\AuthInterface;

final class Actions {

    private $container;
    private $db = null;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    private function db() {
        if ($this->db==null) {
            $this->db = $this->container->get('db')();
        }
        return $this->db;
    }

    public function rebuild($actions) {
        $db = $this->db();
        try {
            $sql = 'SELECT a.* FROM sys_actions a ORDER BY a.class';
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $preserves = [];
            foreach ($rows as $row) {
                $key = $row->method.'-'.$row->class;
                $preserves[$key] = $row;
            }

            $maps = [];

            $auth = $this->container->get(AuthInterface::class);
            $db->beginTransaction();
            $affected = $db->exec("TRUNCATE TABLE sys_actions");
            $sql = "REPLACE INTO sys_actions (route_id, method, class, auth) VALUES (:route_id, :method, :class, :auth)"; // nanti hapus on duplicate
            $stmt = $db->prepare($sql);
            foreach ($actions as $action) {
                $block = $action->auth;
                $key = $action->method.'-'.$action->class;
                if (\array_key_exists($key, $preserves)) {
                    $block = $preserves[$key]->auth;
                    $maps[$preserves[$key]->route_id] = $action->route_id;
                }
                $stmt->execute([':route_id' => $action->route_id, ':method' => $action->method, ':class' => $action->class, ':auth' => $block]);
            }
            $auth->flushRoles($maps);
            $db->commit();

            return true;
        } catch (\Exception $e) {
            echo $e->getMessage();
            $db->rollBack();
        }
        return false;
    }

    private function log($error) {
        $logger = $this->container->get('monolog');
        $logger->info($error);
    }
}
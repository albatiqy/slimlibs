<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs;

use Psr\Container\ContainerInterface;

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
            $db->beginTransaction();
            $affected = $db->exec("TRUNCATE TABLE sys_actions");
            $sql = "INSERT INTO sys_actions (route_id, method, class, auth) VALUES (:route_id, :method, :class, :auth)";
            $stmt = $db->prepare($sql);
            foreach ($actions as $action) {
                $stmt->execute([':route_id' => $action->route_id, ':method' => $action->method, ':class' => $action->class, ':auth' => $action->auth]);
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
        }
        return false;
    }

    private function log($error) {
        $logger = $this->container->get('monolog');
        $logger->info($error);
    }
}
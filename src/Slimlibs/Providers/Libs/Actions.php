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
            $sql = "INSERT INTO sys_actions (class, block) VALUES (:class, :block)";
            $stmt = $db->prepare($sql);
            foreach ($actions as $action=>$block) {
                $stmt->execute([':class' => $action, ':block' => $block]);
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
<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs;

use Psr\Container\ContainerInterface;
use Albatiqy\Slimlibs\Support\Helper\CodeOut;
use Albatiqy\Slimlibs\Support\Helper\Fs;

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

    public function getRoles($uid) {
        $frole = \APP_DIR . '/var/users/'.$uid.'/roles.php';
        $roles = [];
        if (\file_exists($frole)) {
            $roles = require $frole;
        } else {
            $db = $this->db();
            $sql = 'SELECT a.* FROM auth_groups_users b JOIN auth_groups c ON b.group_id=c.group_id '.
                'JOIN auth_groups_actions d ON c.group_id=d.group_id '.
                'JOIN sys_actions a ON d.route_id=a.route_id '.
                'WHERE b.user_id=:user_id GROUP BY a.route_id ORDER BY a.class';
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $uid]);
            $actions = $stmt->fetchAll();
            foreach ($actions as $action) {
                if (!isset($roles[$action->method])) {
                    $roles[$action->method] = [$action->class => true];
                } else {
                    $roles[$action->method][$action->class] = true;
                }
            }
            $mkdir = \dirname($frole);
            if (!\is_dir($mkdir)) {
                \umask(2);
                \mkdir($mkdir, 0777, true);
            }
            $fileout = "<?php\nreturn " . CodeOut::fromArray($roles) . ';';
            \file_put_contents($frole, $fileout);
        }
        return $roles;
    }

    public function rebuild($actions) {
        $db = $this->db();
        $base_dir = \APP_DIR . '/var/users/common/roles';
        try {
            $auth = ['open'=>[], 'authenticate'=>[]];
            foreach ($auth as $dir=>$k) {
                $mkdir = $base_dir.'/'.$dir;
                if (!\is_dir($mkdir)) {
                    \umask(2);
                    \mkdir($mkdir, 0777, true);
                }
            }

            $sql = 'SELECT a.* FROM sys_actions a ORDER BY a.class';
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $preserves = [];
            foreach ($rows as $row) {
                $preserves[$row->method.'-'.$row->class] = $row->auth;
            }

            $db->beginTransaction();
            $affected = $db->exec("TRUNCATE TABLE sys_actions");
            $sql = "REPLACE INTO sys_actions (route_id, method, class, auth) VALUES (:route_id, :method, :class, :auth)"; // nanti hapus on duplicate
            $stmt = $db->prepare($sql);
            $methods = [];
            foreach ($actions as $action) {
                $block = $action->auth;
                $key = $action->method.'-'.$action->class;
                if (\array_key_exists($key, $preserves)) {
                    $block = $preserves[$key];
                }
                $stmt->execute([':route_id' => $action->route_id, ':method' => $action->method, ':class' => $action->class, ':auth' => $block]);
                if (!\in_array($action->method, $methods)) {
                    $methods[] = $action->method;
                }
                if ($block==0) {
                    if (!isset($auth['open'][$action->method])) {
                        $auth['open'][$action->method] = [$action->class => true];
                    } else {
                        $auth['open'][$action->method][$action->class] = true;
                    }
                }
                if ($block==1) {
                    if (!isset($auth['authenticate'][$action->method])) {
                        $auth['authenticate'][$action->method] = [$action->class => true];
                    } else {
                        $auth['authenticate'][$action->method][$action->class] = true;
                    }
                }
            }
            foreach ($auth as $dir=>$entries) {
                foreach ($methods as $method) {
                    if (isset($entries[$method])) {
                        $fileout = "<?php\nreturn " . CodeOut::fromArray($entries[$method]) . ';';
                        \file_put_contents($base_dir.'/'.$dir.'/'.$method.'.php', $fileout);
                    } else {
                        \file_put_contents($base_dir.'/'.$dir.'/'.$method.'.php', "<?php\nreturn [];");
                    }
                }
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            Fs::rmDir($base_dir, false);
        }
        return false;
    }

    private function log($error) {
        $logger = $this->container->get('monolog');
        $logger->info($error);
    }
}
<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs;

use Psr\Container\ContainerInterface;

final class Jobs {

    public const STATE_RESET = 0;
    public const STATE_FINISHED = 1;
    public const STATE_ERROR = 2;

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

    public function remains() {
        $db = $this->db();
        $sql = "select a.* from sys_jobs a where a.state=0 order by a.id limit 0,5";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function flagFinished($jobId) {
        $db = $this->db();
        $stmt = $db->prepare(
            'UPDATE sys_jobs SET state=:state WHERE id=:id'
        );
        $stmt->execute([
            ':state' => self::STATE_FINISHED,
            ':id' => $jobId
        ]);
    }

    public function register($job) {
        $jobName = $job->getMapName();
        $fileload = \APP_DIR . '/var/jobs/' . $jobName . '.php';
        if (!\file_exists($fileload)) {
            throw new \Exception('job tidak diinisialisasi');
        }

        $cmdmanifest = require $fileload;
        $props = $cmdmanifest['options']['props'];
        $data = [];
        foreach ($props as $prop=>$var) {
            $data[$prop] = $job->{$var['name']};
        }

        $sql = "INSERT INTO sys_jobs (job, `data`, state) VALUES (:job,:data,0)";
        $db = $this->db();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':job' => $jobName,
            ':data' => \json_encode($data)
        ]);
    }

    public function setResult($id, $state, $logs = null, $output = null) {
        $db = $this->db();
        $stmt = $db->prepare(
            'UPDATE sys_jobs SET `output`=:output,logs=:logs,state=:state WHERE id=:id'
        );
        $stmt->execute([
            ':output' => $output,
            ':logs' => $logs,
            ':state' => $state,
            ':id' => $id
        ]);
    }

    private function log($error) {
        $logger = $this->container->get('monolog');
        $logger->info($error);
    }
}
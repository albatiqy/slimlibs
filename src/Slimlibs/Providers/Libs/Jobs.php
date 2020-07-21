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
        $sql = "select a.* from sys_jobs a where (a.state=0 AND a.schedule IS NULL) order by a.id limit 0,5";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    public function remainSchedules() {
        $db = $this->db();
        $sql = "select a.* from sys_jobs a where (a.state=0 AND a.schedule is NOT NULL) order by a.schedule limit 0,20";
        $stmt = $db->query($sql);
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

        $sql = "INSERT INTO sys_jobs (job, schedule, `data`, state) VALUES (:job,NULL,:data,0)";
        $db = $this->db();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':job' => $jobName,
            ':data' => \json_encode($data)
        ]);
        return $db->lastInsertId();
    }

    public function schedule($job, \DateTime $time) {
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

        $sql = "INSERT INTO sys_jobs (job, schedule, `data`, state) VALUES (:job,:schedule,:data,0)";
        $db = $this->db();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':job' => $jobName,
            ':schedule' => $time->format('Y-m-d H:i:s'),
            ':data' => \json_encode($data)
        ]);
        return $db->lastInsertId();
    }

    public function reschedule($job_id, \DateTime $time) {
        $db = $this->db();
        $sql = "select a.* from sys_jobs a where a.id=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $job_id
        ]);
        $row = $stmt->fetch();
        if (\is_object($row)) {
            if ($row->schedule) {
                $stmt = $db->prepare(
                    'UPDATE sys_jobs SET schedule=:schedule WHERE id=:id'
                );
                $stmt->execute([
                    ':schedule' => $time->format('Y-m-d H:i:s'),
                    ':id' => $job_id
                ]);
            }
        }
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
        $this->container->logError($error);
    }
}
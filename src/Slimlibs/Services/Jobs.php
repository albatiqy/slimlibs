<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Services;

use Albatiqy\Slimlibs\Providers\Database\MySqlDbService;

final class Jobs extends MySqlDbService {

    protected const TABLE_NAME = 'sys_jobs a';
    protected const PRIMARY_KEY = 'id';
    protected const ENTITY_NAME = 'job';
    protected const COLUMN_DEFS = [
        'id' => ['db' => 'a.id', 'type' => self::TYPE_NUMERIC],
        'job' => ['db' => 'a.job', 'type' => self::TYPE_STRING],
        'data' => ['db' => 'a.data', 'type' => self::TYPE_STRING],
        'output' => ['db' => 'a.output', 'type' => self::TYPE_STRING],
        'logs' => ['db' => 'a.logs', 'type' => self::TYPE_STRING],
        'state' => ['db' => 'a.state', 'type' => self::TYPE_NUMERIC]
    ];

    protected function getRules($opr) {
        if ($opr == self::RULE_CREATE) {
            return [
                'job' => ['required'],
                'data' => ['required']
            ];
        }
        $rules = [
            'job' => [],
            'data' => [],
            'output' => [],
            'logs' => [],
            'state' => []
        ];
        return $rules;
    }

    public function remains() {
        $db = $this->db();
        $table = self::TABLE_NAME;
        $sql = "select a.* from $table where a.state=0 order by a.id limit 0,5";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function flagFinished($jobId) {
        $db = $this->db();
        $stmt = $db->prepare(
            'UPDATE sys_jobs SET state=1 WHERE id=:id'
        );
        $stmt->execute([
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

        $this->create([
            'job' => $jobName,
            'data' => \json_encode($data)
        ]);
    }
}
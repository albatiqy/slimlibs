<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Commands;

use Albatiqy\Slimlibs\Command\AbstractCommand;

/**
 * Builder Playground
 *
 */

final class Builder extends AbstractCommand {

    public function main() {
        $this->showHelp();
    }

   /**
     * Memproses antrian job background
     *
     * @alias [describe]
     * @arg [table|required] nama tabel
     */
    public function tableDescribe() {
        $db = $this->container->get('db')();
        $sql = "DESCRIBE ".$this->args[0];
        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll();

        $key = null;
        $auto = false;
        $tbdef = [];
        $insert1 = [];
        $insert2 = [];
        $insert3 = [];
        $update = [];
        $rules1 = [];
        $rules2 = [];
        $labels = [];
        foreach ($rows as $row) {
            $ruled = [];
            $type = 'self::TYPE_NUMERIC';
            \preg_match('/(\w+)\(*([^\)]*)\)*/', $row->Type, $matches);
            //print_r($matches);
            $null = false;
            if ($row->Null=='YES' && $row->Default===null) {
                $null = true;
            }
            if (!$null) {
                $ruled[] = "'required'";
            }
            switch ($matches[1]) {
                case 'varchar':
                case 'char':
                    $type = 'self::TYPE_STRING';
                    $ruled[] = "'max_length' => {$matches[2]}";
                break;
                case 'date':
                    $ruled[] = "'date'";
                break;
                case 'datetime':
                    $ruled[] = "'datetime'";
                break;

            }
            if ($row->Key=='PRI') {
                $key = $row->Field;
                if (\strpos($row->Extra, 'AUTO_INCREMENT') >= 0) {
                    $auto = true;
                }
            }
            $tbdef[] = "'{$row->Field}' => ['db'=>'a.{$row->Field}', 'type'=>$type".($null?", 'null'=>true":'')."]";
            $rules1[] = "'{$row->Field}' => [".\implode(", ", $ruled)."]";
            $rules2[] = "'{$row->Field}'";

            $insert1[] = $row->Field;
            $insert2[] = ":".$row->Field;
            $insert3[] = "':{$row->Field}' => \${$row->Field}";
            $update[] = "{$row->Field}=:{$row->Field}";
            $ucwords = \ucwords(\str_replace('_', ' ', $row->Field));
            $labels[] = "'{$row->Field}' => '{$ucwords}'";
        }
        $buff = \implode(",\n",$tbdef)."\n\n\n";
        $buff .= \implode(",", $insert1)."\n\n\n";
        $buff .= \implode(",\n",$rules1)."\n\n\n";
        $buff .= \implode(",", $insert2)."\n\n\n";
        $buff .= \implode(",\n", $insert3)."\n\n\n";
        $buff .= \implode(",", $update)."\n\n\n";
        $buff .= \implode(",\n", $rules2)."\n\n\n";
        $buff .= \implode(",\n", $labels)."\n\n\n";

        echo $buff;
        \file_put_contents(\APP_DIR. '/var/tmp/'.$this->args[0].'_dump.txt', $buff);

/*
        $fileload = \APP_DIR . '/var/jobs/' . $jobname . '.php';
        if (!\file_exists($fileload)) {
            $this->error('job tidak ditemukan');
            exit;
        }
        $cmdmanifest = require $fileload;
        $reflect = $cmdmanifest['handler'];
        $reflect = new \ReflectionClass($reflect); // chk abstract command?

        $instance = $reflect->newInstance($this->container);
        $props = $cmdmanifest['options']['props'];
        $data = \json_decode($this->args[0], true);
        foreach ($data as $k => $v) {
            $property = $reflect->getProperty($props[$k]['name']);
            $property->setValue($instance, $v);
        }
        $output = '';
        $logs = '';
        try {
            $result = $instance->run();
            $output = $instance->getOutput();
            $logs = $instance->getLogs();
            $this->writeLine('Output : '. $output);
            $this->writeLine('Logs : '. $logs);
        } catch (\Exception $e) {
            $this->error('Logs : '.$e->getMessage()."\r\n".$logs);
            $this->writeLine('Output : '. $output);
        }
*/
    }

    /**
     * Service generator
     *
     * @alias [service]
     * @opt [$svcname|svcname|s|svcname] nama kelas
     * @arg [tabel|required] tabel db
     */
    public function createService($svcname = null) {
    }

    /**
     * Rest API generator
     *
     * @alias [restapi]
     * @opt [$primary_key|primary_key|p|primary_key] primary key
     * @arg [service|required] kelas Service
     */
    public function createRestApi($primary_key = 'id') {
    }
}
<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Commands;

use Albatiqy\Slimlibs\Command\AbstractCommand;
use Albatiqy\Slimlibs\Command\TableFormatter;
use Albatiqy\Slimlibs\Services\Jobs;
use Albatiqy\Slimlibs\Support\Helper\CodeOut;
use Albatiqy\Slimlibs\Support\Util\DocBlock;
use Albatiqy\Slimlibs\Support\Helper\Fs;

/**
 * Manajemen job
 *
 */

final class JobRunner extends AbstractCommand {

    /**
     * Menampilkan daftar job tersedia
     *
     * @alias [list]
     */
    public function list() {
        $tf = new TableFormatter();
        $dir = \APP_DIR . '/var/jobs';
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $cmdinfo = require $dir . '/' . $fileinfo->getBasename();
                echo $tf->format(
                    ['20%', '*'],
                    [
                        '   ' . $fileinfo->getBasename('.' . $fileinfo->getExtension()),
                        $cmdinfo['options']['desc'],
                    ],
                    ["cyan", "green"]
                );
            }
        }
    }

    /**
     * Remapping kelas job
     *
     * @alias [remap]
     */
    public function remap() { //v2============
        $vdir = \APP_DIR . '/var/jobs';
        Fs::rmDir($vdir, false);

        $dir = \LIBS_DIR . '/src/Slimlibs/Command/Jobs';
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $tomap = $fileinfo->getBasename('.' . $fileinfo->getExtension());
                $this->writeLine('mapping '.$tomap);
                $reflect = new \ReflectionClass('\\Albatiqy\\Slimlibs\\Command\\Jobs\\' . $tomap);
                // check parent here
                $result = $this->parseClass($reflect);
                $fileout = "<?php\nreturn [\n    \"handler\" => " . $reflect->getName() . "::class,\n    \"options\" => " . CodeOut::fromArray($result) . "\n];";
                \file_put_contents($vdir . '/' . $reflect->getConstant('MAP') . '.php', $fileout);
            }
        }
        $dir = \APP_DIR . '/src/Jobs';
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $tomap = $fileinfo->getBasename('.' . $fileinfo->getExtension());
                $this->writeLine('mapping '. $tomap);
                $reflect = new \ReflectionClass('\\App\\Jobs\\' . $tomap);
                // check parent here
                $result = $this->parseClass($reflect);
                $fileout = "<?php\nreturn [\n    \"handler\" => " . $reflect->getName() . "::class,\n    \"options\" => " . CodeOut::fromArray($result) . "\n];";
                \file_put_contents($vdir . '/' . $reflect->getConstant('MAP') . '.php', $fileout);
            }
        }
    }

    /**
     * Memproses antrian job background
     *
     * @alias [serve]
     */
    public function serve() { //==============logging & locking
        $dir = \APP_DIR . '/var/schedules';
        $dirs = \array_diff(\scandir($dir), ['..', '.']);
        $trunc = \substr((string)\time(), 0, -1).'0';
        $time = (int)$trunc;
        if (\count($dirs) > 0) {
            $process = [];
            foreach ($dirs as $mfile) {
                $manifest = require $dir.'/'.$mfile;
                if ($time%(int)$manifest['schedule']==0) {
                    $process[$manifest['jobname']] = $manifest['data'];
                }
            }
            foreach ($process as $job=>$data) {
                $fileload = \APP_DIR . '/var/jobs/' . $job . '.php';
                if (!\file_exists($fileload)) {
                    $this->log('job tidak ditemukan');
                } else {
                    $cmdmanifest = require $fileload;
                    $reflect = $cmdmanifest['handler'];
                    $reflect = new \ReflectionClass($reflect); // chk abstract command?

                    $instance = $reflect->newInstance($this->container);
                    $props = $cmdmanifest['options']['props'];
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
                        if (\trim($output)) {
                            $this->log('Output : '. $output);
                        }
                        if (\trim($logs)) {
                            $this->log('Logs : '. $logs);
                        }
                    } catch (\Exception $e) {
                        $this->log('Error Logs : '.$e->getMessage()."\r\n".$logs);
                        $this->log('Output : '. $output);
                    }
                }
            }
        }

        if ($time%60==0) {
            $da_jobs = Jobs::getInstance();
            $jobs = $da_jobs->remains();
            foreach ($jobs as $job) {
                $fileload = \APP_DIR . '/var/jobs/' . $job->job . '.php';
                if (!\file_exists($fileload)) {
                    $da_jobs->dbUpdate([
                        'logs' => 'job tidak ditemukan',
                        'id' => $job->id
                    ]);
                } else {
                    $cmdmanifest = require $fileload;
                    $reflect = $cmdmanifest['handler'];
                    $reflect = new \ReflectionClass($reflect);

                    $instance = $reflect->newInstance($this->container); // check parent type
                    $props = $cmdmanifest['options']['props'];
                    $data = \json_decode($job->data, true);
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
                        //$da_jobs->flagFinished($job->id);
                        $da_jobs->dbUpdate([
                            'logs' => \substr($logs,0,1000),
                            'output' => \substr($output,0,2000),
                            'state' => ($result===true?1:0),
                            'id' => $job->id
                        ]);
                    } catch (\Exception $e) {
                        $da_jobs->dbUpdate([
                            'logs' => \substr($e->getMessage()."\r\n".$logs,0,1000),
                            'output' => \substr($output,0,2000),
                            'id' => $job->id
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Memproses antrian job background
     *
     * @alias [run]
     * @opt [$jobname|jobname|j|jobname] nama job yang akan dieksekusi
     * @arg [data|required] data berupa JSON
     */
    public function run($jobname = null) {
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
    }

    public function main() {
        $this->showHelp();
    }

    private function parseClass($reflect) {
        $result = [];
        $doc_block = new DocBlock($reflect);
        $result['desc'] = $doc_block->getComment();
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $props = [];
        foreach ($properties as $property) {
            $doc_block = new DocBlock($property);
            $pname = $property->getName();
            $alias = $doc_block->getTagValue('alias');
            if ($alias != null) {
                $props[$alias] = ['name' => $pname, 'desc' => $doc_block->getComment()];
            }
        }
        $result['props'] = $props;
        return $result;
    }

    protected function log($error) {
        $logger = $this->container->get('monolog');
        $logger->info($error);
    }
}
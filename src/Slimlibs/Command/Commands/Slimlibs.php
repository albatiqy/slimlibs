<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Commands;

use Albatiqy\Slimlibs\Command\AbstractCommand;
use Albatiqy\Slimlibs\Support\Helper\Fs;
use Albatiqy\Slimlibs\Support\Util\DocBlock;
use Albatiqy\Slimlibs\Providers\Libs\Actions;
use Albatiqy\Slimlibs\Support\Helper\CodeOut;

/**
 * Slimlibs tools
 *
 */

final class Slimlibs extends AbstractCommand {

    /**
     * Inisialisasi var directory
     *
     * @alias [initvar]
     */
    public function initVar() {
        $parent = \APP_DIR . '/var';
        $this->writeLine('removing content ' . $parent);
        Fs::rmDir($parent, false);
        $this->writeLine('creating .gitignore');
        $fileout = "*\r\n!archive\r\n!.gitignore";
        \file_put_contents($parent . '/.gitignore', $fileout);

        $this->createVarDir('/archive');
        $this->createVarDir('/cache');
        $this->createVarDir('/cache/pages');
        $this->createVarDir('/commands');
        $this->createVarDir('/configs');
        $this->createVarDir('/jobs');
        $this->createVarDir('/schedules');
        $this->createVarDir('/telegramcmds');
        $this->createVarDir('/log');
        $this->createVarDir('/resources');
        $this->createVarDir('/resources/config');
        $this->createVarDir('/resources/imgcache');
        $this->createVarDir('/resources/imgcache/keys');
        $this->createVarDir('/resources/imgcache/srcs');
        $this->createVarDir('/resources/media/uploads', true);
        $this->createVarDir('/resources/page');
        $this->createVarDir('/resources/page/pages');
        $this->createVarDir('/resources/page/layout');
        $this->createVarDir('/resources/templates/mail', true);
        $this->createVarDir('/resources/view/templates', true);
        $this->createVarDir('/resources/view/profiles');
        $this->createVarDir('/tmp');
        $this->createVarDir('/users');

        $parent = \APP_DIR . '/var/archive';
        $this->writeLine('creating archive .gitignore');
        $fileout = "!*";
        \file_put_contents($parent . '/.gitignore', $fileout);
    }

    /**
     * Inisialisasi autentikasi dan otorisasi
     *
     * @alias [initauth]
     */
    public function initAuth() {
        $app = \Slim\Factory\AppFactory::create();
        $routeCollector = $app->getRouteCollector();
        $dsettings = $this->container->get('settings');
        $settings = [
            'backend_path' => $dsettings['backend_path'],
            'login_path' => $dsettings['login_path']
        ];
        (require \LIBS_DIR . '/web/route.php')($app);
        $routes = $routeCollector->getRoutes();
        $actions = [];
        foreach ($routes as $route) {
            $callable = $route->getCallable();
            $class = new \ReflectionClass('\\'.$callable);
            $action = ['route_id' => $route->getIdentifier(), 'method' => $route->getMethods()[0], 'class' => $callable, 'auth' => $this->parseAuthNotation($class)];
            $actions[] = (object) $action;
        }
        $da_actions = $this->container->get(Actions::class);
        if ($da_actions->rebuild($actions)) {
            $this->success("TRANSACTION SUCCESS");
        } else {
            $this->error("TRANSACTION ERROR");
        }
        /*
        $dir = \APP_DIR . '/src/Actions';
        $cpos = \strlen($dir);

        $dir_iterator = new \RecursiveDirectoryIterator($dir);
        $dir_iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
        $actions = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $path = $file->getPath();
                $basef = '\\App\\Actions'.\str_replace('/','\\',\substr($path, $cpos).'\\'.$file->getBasename('.'.$file->getExtension()));
                $this->writeLine('mapping '.$basef);
                $class = new \ReflectionClass($basef);
                $actions[$class->getName()] = $this->parseAuthNotation($class);
            }
        }
        $da_actions = $this->container->get(Actions::class);
        if ($da_actions->rebuild($actions)) {
            $this->success("TRANSACTION SUCCESS");
        } else {
            $this->error("TRANSACTION ERROR");
        }
        */
    }

    /**
     * Inisialisasi schedule
     *
     * @alias [initschedules]
     */
    public function initSchedules() {
        $dir = \LIBS_DIR . '/src/Slimlibs/Command/Schedules';
        $schedules = [];
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $tomap = $fileinfo->getBasename('.' . $fileinfo->getExtension());
                $this->writeLine('mapping '.$tomap);
                $reflect = new \ReflectionClass('\\Albatiqy\\Slimlibs\\Command\\Schedules\\' . $tomap);
                $schedules[$reflect->getConstant('MAP')] = $reflect;
            }
        }
        $dir = \APP_DIR . '/src/Command/Schedules';
        if (\is_dir($dir)) {
            $iterator = new \DirectoryIterator($dir);
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $tomap = $fileinfo->getBasename('.' . $fileinfo->getExtension());
                    $this->writeLine('mapping '.$tomap);
                    $reflect = new \ReflectionClass('\\App\\Command\\Schedules\\' . $tomap);
                    $schedules[$reflect->getConstant('MAP')] = $reflect;
                }
            }
        }
        $vdir = \APP_DIR . '/var/schedules';
        Fs::rmDir($vdir, false);
        foreach ($schedules as $map=>$reflect) {
            $job_reflect = new \ReflectionClass($reflect->getConstant('JOB_CLASS'));
            $job_instance = $job_reflect->newInstance($this->container);
            $fileout = "<?php\nreturn [\n    \"jobname\" => \"" . $job_instance->getMapName() . "\",\n    \"schedule\" => \"".$reflect->getConstant('SCHEDULE')."\",\n    \"data\" => " . CodeOut::fromArray($reflect->getConstant('JOB_DATA')) . "\n];";
            \file_put_contents($vdir. '/' . $map . '.php', $fileout);
        }
    }

    private function createVarDir($dir, $recursive = false) {
        $path = \APP_DIR . '/var'.$dir;
        if ($recursive) {
            $this->writeLine('creating ' . $path  . ' recursively');
            mkdir($path, 0777, true);
        } else {
            $this->writeLine('creating ' . $path);
            mkdir($path);
        }
    }

    private function parseAuthNotation($class) {
        $doc_block = new DocBlock($class);
        $result = 0;
        if ($doc_block->tagExists('authorize')) {
            $result = 2;
        } else {
            if ($doc_block->tagExists('authenticate')) {
                $result = 1;
            }
        }
        return $result;
    }

    public function main() {
        $this->showHelp();
    }
}
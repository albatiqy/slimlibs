<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Commands;

use Albatiqy\Slimlibs\Command\AbstractCommand;
use Albatiqy\Slimlibs\Support\Helper\Fs;
use Albatiqy\Slimlibs\Support\Util\DocBlock;
use Albatiqy\Slimlibs\Services\Actions;

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
        $this->createVarDir('/log');
        $this->createVarDir('/resources');
        $this->createVarDir('/resources/config');
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
                $actions[$class->getName()] = $this->parseAuthCommand($class);
            }
        }
        $da_actions = Actions::getInstance();
        if ($da_actions->rebuild($actions)) {
            $this->success("TRANSACTION SUCCESS");
        } else {
            $this->error("TRANSACTION ERROR");
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

    private function parseAuthCommand($class) {
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
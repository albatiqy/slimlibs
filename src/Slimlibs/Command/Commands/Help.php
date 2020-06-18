<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Commands;

use Albatiqy\Slimlibs\Command\AbstractCommand;
use Albatiqy\Slimlibs\Command\TableFormatter;
use Albatiqy\Slimlibs\Support\Util\DocBlock;

/**
 * Manual Slimlibs Cli
 *
 */

final class Help extends AbstractCommand {

    /**
     * @opt [path|p|strpath] filter lokasi scanning nilai "app" atau "libs"
     */
    public $filter = null;

    /**
     * Menampilkan kelas tersedia untuk dimapping
     *
     * @alias [class]
     */
    public function listClass() {
        echo "\n";
        //strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $tf = new TableFormatter();
        $this->writeLine("available class:\n");
        $dir = \APP_DIR . '/src/Command/Commands';
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $class = new \ReflectionClass('\\App\\Command\\Commands\\' . $fileinfo->getBasename('.' . $fileinfo->getExtension()));
                $doc_block = new DocBlock($class);
                echo $tf->format(
                    ['20%', '*'],
                    [
                        '   ' . $class->getShortName(),
                        $doc_block->getComment(),
                    ],
                    ["cyan", "green"]
                );
            }
        }
        echo "\n\n";
    }

    public function main() {
        echo "\n";
        //strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $tf = new TableFormatter();
        $this->writeLine("available commands:\n");
        $base_dir = \LIBS_DIR . '/cli/commands';
        $this->lstcmd($base_dir, $tf);
        $base_dir = \APP_DIR . '/var/commands';
        if (\is_dir($base_dir)) {
            $this->lstcmd($base_dir, $tf);
        } else {
            echo "\nplease run \"sudo -u www-data php cli slimlibs initvar\"";
        }
        echo "\n\n";
        //$this->showHelp();
    }

    private function lstcmd($dir, $tf) {
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $cmdinfo = require $dir . '/' . $fileinfo->getBasename();
                echo $tf->format(
                    ['20%', '*'],
                    [
                        '   ' . $fileinfo->getBasename('.' . $fileinfo->getExtension()),
                        $cmdinfo['options']['help'],
                    ],
                    ["cyan", "green"]
                );
            }
        }
    }
}
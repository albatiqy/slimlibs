<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Commands;

use Albatiqy\Slimlibs\Command\AbstractCommand;
use Albatiqy\Slimlibs\Support\Helper\Fs;
use Albatiqy\Slimlibs\Support\Helper\CodeOut;
use Albatiqy\Slimlibs\Support\Util\DocBlock;

/**
 * Telegram tools
 *
 */

final class Telegram extends AbstractCommand {

    /**
     * Inisialisasi bot commands
     *
     * @alias [initbotcommands]
     */
    public function initCommands() {
        $dir = \LIBS_DIR . '/src/Slimlibs/Command/TelegramCommands';
        $commands = [];
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $tomap = $fileinfo->getBasename('.' . $fileinfo->getExtension());
                $this->writeLine('mapping '.$tomap);
                $reflect = new \ReflectionClass('\\Albatiqy\\Slimlibs\\Command\\TelegramCommands\\' . $tomap);
                $result = $this->parseClass($reflect);
                $commands[$reflect->getConstant('MAP')] = [$reflect, $result];
            }
        }
        $dir = \APP_DIR . '/src/Command/TelegramCommands';
        if (\is_dir($dir)) {
            $iterator = new \DirectoryIterator($dir);
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $tomap = $fileinfo->getBasename('.' . $fileinfo->getExtension());
                    $this->writeLine('mapping '.$tomap);
                    $reflect = new \ReflectionClass('\\App\\Command\\TelegramCommands\\' . $tomap);
                    $result = $this->parseClass($reflect);
                    $commands[$reflect->getConstant('MAP')] = [$reflect, $result];
                }
            }
        }
        $vdir = \APP_DIR . '/var/telegramcmds';
        Fs::rmDir($vdir, false);
        foreach ($commands as $map=>$val) {
            $fileout = "<?php\nreturn [\n    \"handler\" => " . $val[0]->getName() . "::class,\n    \"options\" => " . CodeOut::fromArray($val[1]) . "\n];";
            \file_put_contents($vdir . '/' . $map . '.php', $fileout);
        }
    }

    /**
     * Menampilkan user tersedia
     *
     * @alias [users]
     */
    public function listUsers() {
    }

    /**
     * Mengirim pesan ke channel
     *
     * @alias [msgchannel]
     * @arg [text|required] teks yang akan dikirim
     */
    public function sendChannel() {
    }

    /**
     * Mengirim pesan ke user
     *
     * @alias [msguser]
     * @opt [$username|username|u|username] username tujuan
     * @arg [text|required] teks yang akan dikirim
     */
    public function sendUser($username = null) {
    }

    private function parseClass($reflect) {
        $result = [];
        $doc_block = new DocBlock($reflect);
        $result['desc'] = $doc_block->getComment();
        $methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);
        $cmds = [];
        foreach ($methods as $method) {
            $mname = $method->getName();
            if (!\in_array($mname, ['__construct', 'run'])) {
                $cmd = [];
                $doc_block = new DocBlock($method);
                $cmd['desc'] = $doc_block->getComment();
                $map = $doc_block->getTagValue('map');
                if ($map==null) {
                    $map = $mname;
                }
                $cmd['name'] = $mname;
                $cmds[$map] = $cmd;
            }
        }
        $result['commands'] = $cmds;
        return $result;
    }

    public function main() {
        $this->showHelp();
    }
}
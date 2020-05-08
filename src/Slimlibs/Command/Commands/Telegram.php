<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Commands;

use Albatiqy\Slimlibs\Command\AbstractCommand;
use Albatiqy\Slimlibs\Support\Helper\Fs;
use Albatiqy\Slimlibs\Support\Helper\CodeOut;

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
                $commands[$reflect->getConstant('MAP')] = $reflect;
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
                    $commands[$reflect->getConstant('MAP')] = $reflect;
                }
            }
        }
        $vdir = \APP_DIR . '/var/telegramcmds';
        Fs::rmDir($vdir, false);
        foreach ($commands as $map=>$reflect) {
            $fileout = "<?php\nreturn " . $reflect->getName() . "::class;";
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

    public function main() {
        $this->showHelp();
    }
}
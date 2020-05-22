<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\TelegramCommands;

use Albatiqy\Slimlibs\Command\AbstractTelegramCommand;
use Albatiqy\Slimlibs\Providers\Libs\Configs;

final class Help extends AbstractTelegramCommand {

    protected const MAP = 'help';

    public function run($arguments, $message, $bot) {
        $dir = \APP_DIR . '/var/telegramcmds';
        $iterator = new \DirectoryIterator($dir);
        $sb = '';
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $cmdinfo = require $dir . '/' . $fileinfo->getBasename();
                $sb .= '/'.$fileinfo->getBasename('.' . $fileinfo->getExtension()).' '.$cmdinfo['options']['desc']."\r\n";
            }
        }

        $bot->sendUserText($message->chat->id, $sb, $message->message_id);
    }
}
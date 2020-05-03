<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\TelegramCommands;

use Albatiqy\Slimlibs\Command\AbstractTelegramCommand;

final class ListTmpFiles extends AbstractTelegramCommand {

    protected const MAP = 'lstmpfiles';

    public function run($message, $bot) {
        if ($message->chat->type=='private') {
            $bot->sendUser($message->chat->id, 'mbelgedes');
        }
    }
}
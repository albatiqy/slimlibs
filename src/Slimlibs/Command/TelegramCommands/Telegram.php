<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\TelegramCommands;

use Albatiqy\Slimlibs\Command\AbstractTelegramCommand;
use Albatiqy\Slimlibs\Providers\Libs\Configs;

final class Telegram extends AbstractTelegramCommand {

    protected const MAP = 'telegram';

    public function run($arguments, $message, $bot) {
        $this->help($arguments, $message, $bot);
    }

    /**
     * bersihkan message dari channel
     *
     * @map [clearchannel]
     */
    public function clearChannel($arguments, $message, $bot) {
        $data = $bot->deleteChannelMessages();
        \ob_start();
        \print_r($data);
        $obdata = \ob_get_contents();
        \ob_end_clean();
        $bot->sendUserText($message->chat->id, $obdata, $message->message_id);
    }
}
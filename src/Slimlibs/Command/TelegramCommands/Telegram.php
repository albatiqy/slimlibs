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
     * set bersihkan message dari channel
     *
     * @map [clearchannel]
     */
    public function clearChannel($arguments, $message, $bot) {
        $bot->deleteChannelMessages();
    }
}
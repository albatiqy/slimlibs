<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Commands;

use Albatiqy\Slimlibs\Command\AbstractCommand;
use Albatiqy\Slimlibs\Providers\Libs\TelegramBot;

/**
 * Telegram service runner
 *
 */

final class TelegramDaemon extends AbstractCommand {

    public function main() {
        $telegram = $this->container->get(TelegramBot::class);
        //$telegram->messageUser('albatiqy', 'hai');
        $telegram->serve();
    }
}
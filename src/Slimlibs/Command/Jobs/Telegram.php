<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Jobs;

use Albatiqy\Slimlibs\Command\AbstractJob;
use Albatiqy\Slimlibs\Providers\Libs\TelegramBot;

/**
 * Telegram Service
 *
 */
final class Telegram extends AbstractJob {

    protected const MAP = 'telegram';

    protected function handle() {
        return $this->locking(function(){
            $telegram = $this->container->get(TelegramBot::class);
            $telegram->listen();
        });
    }
}
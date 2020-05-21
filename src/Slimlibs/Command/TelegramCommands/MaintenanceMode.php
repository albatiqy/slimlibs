<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\TelegramCommands;

use Albatiqy\Slimlibs\Command\AbstractTelegramCommand;
use Albatiqy\Slimlibs\Providers\Libs\Configs;

final class MaintenanceMode extends AbstractTelegramCommand {

    protected const MAP = 'maintenance';

    public function run($message, $bot) {
        $bot->sendUserText($message->chat->id, 'subcommand required', $message->message_id);
    }

    /**
     * set maintenance mode
     *
     * @map [on]
     */
    public function on($message, $bot) {
        $da = $this->container->get(Configs::class);
        if ($da->set('app.maintenance_mode', 1)) {
            $bot->sendUserText($message->chat->id, 'setting updated', $message->message_id);
        }
    }

    /**
     * set maintenance mode
     *
     * @map [off]
     */
    public function off($message, $bot) {
        $da = $this->container->get(Configs::class);
        if ($da->set('app.maintenance_mode', 0)) {
            $bot->sendUserText($message->chat->id, 'setting updated', $message->message_id);
        }
    }
}
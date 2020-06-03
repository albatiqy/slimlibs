<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Jobs;

use Albatiqy\Slimlibs\Command\AbstractJob;
use Albatiqy\Slimlibs\Providers\Libs\TelegramBot;

/**
 * Send message to telgram channel
 *
 */
final class TelegramChannelText extends AbstractJob {

    protected const MAP = 'tgchtext';

    /**
     * text to send
     *
     * @alias [text]
     */
    public $text = '';

    protected function handle() {
        $telegram = $this->container->get(TelegramBot::class);
        $telegram->messageChannelText($this->text);
        return true;
    }
}
<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

abstract class AbstractTelegramCommand {

    protected const MAP = 'undefined';

    protected $container;
    protected $meta;

    abstract public function run($arguments, $message, $bot);

    public function __construct($container) {
        $this->container = $container;
    }

    public function setMeta($meta) {
        $this->meta = $meta;
    }

    protected function help($arguments, $message, $bot) {
        $sb = '';
        foreach ($this->meta['options']['commands'] as $map=>$command) {
            $sb .= '/'.static::MAP.'.'.$map.' '.$command['desc']."\r\n";
        }
        if ($sb) {
            $bot->sendUserText($message->chat->id, $sb, $message->message_id);
        } else {
            $bot->sendUserText($message->chat->id, 'subcommand required, arguments: '.$arguments, $message->message_id);
        }
    }
}
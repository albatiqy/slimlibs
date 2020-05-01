<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

class Colors {
    const C_RESET = 'reset';
    const C_BLACK = 'black';
    const C_DARKGRAY = 'darkgray';
    const C_BLUE = 'blue';
    const C_LIGHTBLUE = 'lightblue';
    const C_GREEN = 'green';
    const C_LIGHTGREEN = 'lightgreen';
    const C_CYAN = 'cyan';
    const C_LIGHTCYAN = 'lightcyan';
    const C_RED = 'red';
    const C_LIGHTRED = 'lightred';
    const C_PURPLE = 'purple';
    const C_LIGHTPURPLE = 'lightpurple';
    const C_BROWN = 'brown';
    const C_YELLOW = 'yellow';
    const C_LIGHTGRAY = 'lightgray';
    const C_WHITE = 'white';

    protected $colors = [
        self::C_RESET => "\33[0m",
        self::C_BLACK => "\33[0;30m",
        self::C_DARKGRAY => "\33[1;30m",
        self::C_BLUE => "\33[0;34m",
        self::C_LIGHTBLUE => "\33[1;34m",
        self::C_GREEN => "\33[0;32m",
        self::C_LIGHTGREEN => "\33[1;32m",
        self::C_CYAN => "\33[0;36m",
        self::C_LIGHTCYAN => "\33[1;36m",
        self::C_RED => "\33[0;31m",
        self::C_LIGHTRED => "\33[1;31m",
        self::C_PURPLE => "\33[0;35m",
        self::C_LIGHTPURPLE => "\33[1;35m",
        self::C_BROWN => "\33[0;33m",
        self::C_YELLOW => "\33[1;33m",
        self::C_LIGHTGRAY => "\33[0;37m",
        self::C_WHITE => "\33[1;37m",
    ];

    protected $enabled = true;

    public function __construct() {
        if (\function_exists('posix_isatty') && !\posix_isatty(\STDOUT)) {
            $this->enabled = false;
            return;
        }
        if (!\getenv('TERM')) {
            $this->enabled = false;
            return;
        }
    }

    public function enable() {
        $this->enabled = true;
    }

    public function disable() {
        $this->enabled = false;
    }

    public function isEnabled() {
        return $this->enabled;
    }

    public function ptln($line, $color, $channel = \STDOUT) {
        $this->set($color);
        \fwrite($channel, \rtrim($line) . "\n");
        $this->reset();
    }

    public function wrap($text, $color) {
        return $this->getColorCode($color) . $text . $this->getColorCode('reset');
    }

    public function getColorCode($color) {
        if (!$this->enabled) {
            return '';
        }
        if (!isset($this->colors[$color])) {
            throw new CommandException("No such color $color");
        }

        return $this->colors[$color];
    }

    public function set($color, $channel = \STDOUT) {
        \fwrite($channel, $this->getColorCode($color));
    }

    public function reset($channel = \STDOUT) {
        $this->set('reset', $channel);
    }
}
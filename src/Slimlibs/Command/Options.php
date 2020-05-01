<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

class Options {
    protected $setup;
    protected $options = [];
    protected $command = '';
    protected $args = [];
    protected $bin;
    protected $colors;

    public function __construct(Colors $colors = null, array $argv) {
        if (!\is_null($colors)) {
            $this->colors = $colors;
        } else {
            $this->colors = new Colors();
        }

        $this->setup = [
            '' => [
                'opts' => [],
                'args' => [],
                'help' => '',
            ],
        ];

        $this->args = $argv;
        $this->bin = \basename(\array_shift($this->args));

        $this->options = [];
    }

    public function getBin() {
        return $this->bin;
    }

    public function setHelp($help) {
        $this->setup['']['help'] = $help;
    }

    public function registerArgument($arg, $help, $required = true, $command = '') {
        if (!isset($this->setup[$command])) {
            throw new CommandException("Command $command not registered");
        }

        $this->setup[$command]['args'][] = [
            'name' => $arg,
            'help' => $help,
            'required' => $required,
        ];
    }

    public function registerCommand($command, $help) {
        if (isset($this->setup[$command])) {
            throw new CommandException("Command $command already registered");
        }

        $this->setup[$command] = [
            'opts' => [],
            'args' => [],
            'help' => $help,
        ];

    }

    public function registerOption($long, $help, $short = null, $needsarg = false, $command = '') {
        if (!isset($this->setup[$command])) {
            throw new CommandException("Command $command not registered");
        }

        $this->setup[$command]['opts'][$long] = [
            'needsarg' => $needsarg,
            'help' => $help,
            'short' => $short,
        ];

        if ($short) {
            if (\strlen($short) > 1) {
                throw new CommandException("Short options should be exactly one ASCII character");
            }

            $this->setup[$command]['short'][$short] = $long;
        }
    }

    public function checkArguments() {
        $argc = \count($this->args);

        $req = 0;
        foreach ($this->setup[$this->command]['args'] as $arg) {
            if (!$arg['required']) {
                break;
            }
            $req++;
        }

        if ($req > $argc) {
            throw new CommandException("Not enough arguments", CommandException::E_OPT_ARG_REQUIRED);
        }
    }

    public function parseOptions() {
        $non_opts = [];

        $argc = \count($this->args);
        for ($i = 0; $i < $argc; $i++) {
            $arg = $this->args[$i];

            if ($arg == '--') {
                $non_opts = \array_merge($non_opts, \array_slice($this->args, $i + 1));
                break;
            }

            if ($arg == '-') {
                $non_opts = \array_merge($non_opts, \array_slice($this->args, $i));
                break;
            }

            if ($arg[0] != '-') {
                $non_opts = \array_merge($non_opts, \array_slice($this->args, $i));
                break;
            }

            if (\strlen($arg) > 1 && $arg[1] === '-') {
                $arg = \explode('=', \substr($arg, 2), 2);
                $opt = \array_shift($arg);
                $val = \array_shift($arg);

                if (!isset($this->setup[$this->command]['opts'][$opt])) {
                    throw new CommandException("No such option '$opt'", CommandException::E_UNKNOWN_OPT);
                }

                if ($this->setup[$this->command]['opts'][$opt]['needsarg']) {
                    if (\is_null($val) && $i + 1 < $argc && !\preg_match('/^--?[\w]/', $this->args[$i + 1])) {
                        $val = $this->args[++$i];
                    }
                    if (\is_null($val)) {
                        throw new CommandException("Option $opt requires an argument",
                            CommandException::E_OPT_ARG_REQUIRED);
                    }
                    $this->options[$opt] = $val;
                } else {
                    $this->options[$opt] = true;
                }

                continue;
            }

            $opt = \substr($arg, 1);
            if (!isset($this->setup[$this->command]['short'][$opt])) {
                throw new CommandException("No such option $arg", CommandException::E_UNKNOWN_OPT);
            } else {
                $opt = $this->setup[$this->command]['short'][$opt]; // store it under long name
            }

            if ($this->setup[$this->command]['opts'][$opt]['needsarg']) {
                $val = null;
                if ($i + 1 < $argc && !\preg_match('/^--?[\w]/', $this->args[$i + 1])) {
                    $val = $this->args[++$i];
                }
                if (\is_null($val)) {
                    throw new CommandException("Option $arg requires an argument",
                        CommandException::E_OPT_ARG_REQUIRED);
                }
                $this->options[$opt] = $val;
            } else {
                $this->options[$opt] = true;
            }
        }

        $this->args = $non_opts;

        if (!$this->command && $this->args && isset($this->setup[$this->args[0]])) {
            $this->command = \array_shift($this->args);
            $this->parseOptions(); // second pass
        }
    }

    public function getOpt($option = null, $default = false) {
        if ($option === null) {
            return $this->options;
        }

        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return $default;
    }

    public function getCmd() {
        return $this->command;
    }

    public function getArgs() {
        return $this->args;
    }

    public function help() {
        $tf = new TableFormatter($this->colors);
        $text = '';

        $hascommands = (\count($this->setup) > 1);
        foreach ($this->setup as $command => $config) {
            $hasopts = (bool) $this->setup[$command]['opts'];
            $hasargs = (bool) $this->setup[$command]['args'];

            if (!$command) {
                $text .= $this->colors->wrap('USAGE:', Colors::C_BROWN);
                $text .= "\n";
                $text .= '   ' . $this->bin;
                $mv = 2;
            } else {
                $text .= "\n";
                $text .= $this->colors->wrap('   ' . $command, Colors::C_PURPLE);
                $mv = 4;
            }

            if ($hasopts) {
                $text .= ' ' . $this->colors->wrap('<OPTIONS>', Colors::C_GREEN);
            }

            if (!$command && $hascommands) {
                $text .= ' ' . $this->colors->wrap('<COMMAND> ...', Colors::C_PURPLE);
            }

            foreach ($this->setup[$command]['args'] as $arg) {
                $out = $this->colors->wrap('<' . $arg['name'] . '>', Colors::C_CYAN);

                if (!$arg['required']) {
                    $out = '[' . $out . ']';
                }
                $text .= ' ' . $out;
            }
            $text .= "\n";

            if ($this->setup[$command]['help']) {
                $text .= "\n";
                $text .= $tf->format(
                    [$mv, '*'],
                    ['', $this->setup[$command]['help'] . "\n"]
                );
            }

            if ($hasopts) {
                if (!$command) {
                    $text .= "\n";
                    $text .= $this->colors->wrap('OPTIONS:', Colors::C_BROWN);
                }
                $text .= "\n";
                foreach ($this->setup[$command]['opts'] as $long => $opt) {

                    $name = '';
                    if ($opt['short']) {
                        $name .= '-' . $opt['short'];
                        if ($opt['needsarg']) {
                            $name .= ' <' . $opt['needsarg'] . '>';
                        }
                        $name .= ', ';
                    }
                    $name .= "--$long";
                    if ($opt['needsarg']) {
                        $name .= ' <' . $opt['needsarg'] . '>';
                    }

                    $text .= $tf->format(
                        [$mv, '30%', '*'],
                        ['', $name, $opt['help']],
                        ['', 'green', '']
                    );
                    $text .= "\n";
                }
            }

            if ($hasargs) {
                if (!$command) {
                    $text .= "\n";
                    $text .= $this->colors->wrap('ARGUMENTS:', Colors::C_BROWN);
                }
                $text .= "\n";
                foreach ($this->setup[$command]['args'] as $arg) {
                    $name = '<' . $arg['name'] . '>';

                    $text .= $tf->format(
                        [$mv, '30%', '*'],
                        ['', $name, $arg['help']],
                        ['', 'cyan', '']
                    );
                }
            }

            if (!$command && $hascommands) {
                $text .= "\n";
                $text .= $this->colors->wrap('COMMANDS:', Colors::C_BROWN);
                $text .= "\n";
                $text .= $tf->format(
                    [$mv, '*'],
                    ['', 'This tool accepts a command as first parameter as outlined below:']
                );
                $text .= "\n";
            }
        }

        return $text;
    }
}
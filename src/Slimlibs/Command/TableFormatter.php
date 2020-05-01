<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

class TableFormatter {
    protected $border = ' ';
    protected $max = 74;
    protected $colors;

    public function __construct(Colors $colors = null) {
        $width = $this->getTerminalWidth();
        if ($width) {
            $this->max = $width - 1;
        }

        if ($colors) {
            $this->colors = $colors;
        } else {
            $this->colors = new Colors();
        }
    }

    public function getBorder() {
        return $this->border;
    }

    public function setBorder($border) {
        $this->border = $border;
    }

    public function getMaxWidth() {
        return $this->max;
    }

    public function setMaxWidth($max) {
        $this->max = $max;
    }

    protected function getTerminalWidth() {
        if (isset($_SERVER['COLUMNS'])) {
            return (int) $_SERVER['COLUMNS'];
        }

        $process = \proc_open('tput cols', [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        $width = (int) \stream_get_contents($pipes[1]);
        \proc_close($process);

        return $width;
    }

    protected function calculateColLengths($columns) {
        $idx = 0;
        $border = $this->strlen($this->border);
        $fixed = (\count($columns) - 1) * $border;
        $fluid = -1;

        foreach ($columns as $idx => $col) {
            if ((string) \intval($col) === (string) $col) {
                $fixed += $col;
                continue;
            }
            if (\substr($col, -1) == '%') {
                continue;
            }
            if ($col == '*') {
                if ($fluid < 0) {
                    $fluid = $idx;
                    continue;
                } else {
                    throw new CommandException('Only one fluid column allowed!');
                }
            }
            throw new CommandException("unknown column format $col");
        }

        $alloc = $fixed;
        $remain = $this->max - $alloc;

        foreach ($columns as $idx => $col) {
            if (\substr("$col", -1) != '%') {
                continue;
            }
            $perc = \floatval($col);

            $real = (int) \floor(($perc * $remain) / 100);

            $columns[$idx] = $real;
            $alloc += $real;
        }

        $remain = $this->max - $alloc;
        if ($remain < 0) {
            throw new CommandException("Wanted column widths exceed available space");
        }

        if ($fluid < 0) {
            $columns[$idx] += ($remain);
        } else {
            $columns[$fluid] = $remain;
        }

        return $columns;
    }

    public function format($columns, $texts, $colors = []) {
        $columns = $this->calculateColLengths($columns);

        $wrapped = [];
        $maxlen = 0;

        foreach ($columns as $col => $width) {
            $wrapped[$col] = \explode("\n", $this->wordwrap($texts[$col], $width, "\n", true));
            $len = \count($wrapped[$col]);
            if ($len > $maxlen) {
                $maxlen = $len;
            }

        }

        $last = \count($columns) - 1;
        $out = '';
        for ($i = 0; $i < $maxlen; $i++) {
            foreach ($columns as $col => $width) {
                if (isset($wrapped[$col][$i])) {
                    $val = $wrapped[$col][$i];
                } else {
                    $val = '';
                }
                $chunk = $this->pad($val, $width);
                if (isset($colors[$col]) && $colors[$col]) {
                    $chunk = $this->colors->wrap($chunk, $colors[$col]);
                }
                $out .= $chunk;

                if ($col != $last) {
                    $out .= $this->border;
                }
            }
            $out .= "\n";
        }
        return $out;

    }

    protected function pad($string, $len) {
        $strlen = $this->strlen($string);
        if ($strlen > $len) {
            return $string;
        }

        $pad = $len - $strlen;
        return $string . \str_pad('', $pad, ' ');
    }

    protected function strlen($string) {
        $string = \preg_replace("/\33\\[\\d+(;\\d+)?m/", '', $string);

        if (\function_exists('mb_strlen')) {
            return \mb_strlen($string, 'utf-8');
        }

        return \strlen($string);
    }

    protected function substr($string, $start = 0, $length = null) {
        if (\function_exists('mb_substr')) {
            return \mb_substr($string, $start, $length);
        } else {
            if ($length) {
                return \substr($string, $start, $length);
            } else {
                return \substr($string, $start);
            }
        }
    }

    protected function wordwrap($str, $width = 75, $break = "\n", $cut = false) {
        $lines = \explode($break, $str);
        foreach ($lines as &$line) {
            $line = \rtrim($line);
            if ($this->strlen($line) <= $width) {
                continue;
            }
            $words = \explode(' ', $line);
            $line = '';
            $actual = '';
            foreach ($words as $word) {
                if ($this->strlen($actual . $word) <= $width) {
                    $actual .= $word . ' ';
                } else {
                    if ($actual != '') {
                        $line .= \rtrim($actual) . $break;
                    }
                    $actual = $word;
                    if ($cut) {
                        while ($this->strlen($actual) > $width) {
                            $line .= $this->substr($actual, 0, $width) . $break;
                            $actual = $this->substr($actual, $width);
                        }
                    }
                    $actual .= ' ';
                }
            }
            $line .= \trim($actual);
        }
        return \implode($break, $lines);
    }
}
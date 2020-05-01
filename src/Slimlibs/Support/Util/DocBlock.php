<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Util;

class DocBlock {

    protected $raw;
    protected $tags;
    protected $comment;

    public function __construct($reflect) {
        $reflect = $reflect->getDocComment();
        $this->parseDocBlock($reflect);
    }

    public function getRaw() {
        return $this->raw;
    }

    public function getComment() {
        return $this->comment;
    }

    public function getTags() {
        return $this->tags;
    }

    public function getTag($name, $default = null, $asArray = false) {
        if (!isset($this->tags[$name])) {
            if ($asArray) {
                return [];
            }
            return $default;
        }

        return ($asArray && !\is_array($this->tags[$name]))
        ? [$this->tags[$name]] : $this->tags[$name];
    }

    public function getTagValue($tag) {
        $tag = $this->getTag($tag);
        if ($tag != null) {
            \preg_match('/^\[.*?\]/', $tag, $mtch);
            if (\count($mtch) > 0) {
                $conf = \substr($mtch[0], 1, -1);
                return \trim($conf);
            }
        }
        return null;
    }

    public function __get($name) {
        return $this->getTag($name);
    }

    public function tagExists($name) {
        return \array_key_exists($name, $this->tags);
    }

    protected function parseDocBlock($raw) {
        $this->raw = $raw;
        $this->tags = [];
        $raw = \str_replace("\r\n", "\n", $raw);
        $lines = \explode("\n", $raw);
        $matches = null;

        switch (\count($lines)) {
        case 1:
            // handle single-line docblock
            if (!\preg_match('#\\/\\*\\*([^*]*)\\*\\/#', $lines[0], $matches)) {
                return;
            }
            $lines[0] = \substr($lines[0], 3, -2);
            break;

        case 2:
            // probably malformed
            return;

        default:
            // handle multi-line docblock
            \array_shift($lines);
            \array_pop($lines);
            break;
        }

        foreach ($lines as $line) {
            $line = \preg_replace('#^[ \t\*]*#', '', $line);

            if (\strlen($line) < 2) {
                continue;
            }

            if (\preg_match('#@([^ ]+)(.*)#', $line, $matches)) {
                $tag_name = $matches[1];
                $tag_value = \trim($matches[2]);

                // If this tag was already parsed, make its value an array
                if (isset($this->tags[$tag_name])) {
                    if (!\is_array($this->tags[$tag_name])) {
                        $this->tags[$tag_name] = [$this->tags[$tag_name]];
                    }

                    $this->tags[$tag_name][] = $tag_value;
                } else {
                    $this->tags[$tag_name] = $tag_value;
                }
                continue;
            }

            $this->comment .= "$line\n";
        }
        if ($this->comment!=null) {
            $this->comment = \trim($this->comment);
        }
    }
}
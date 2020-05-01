<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Commands;

use Albatiqy\Slimlibs\Command\AbstractCommand;
use Albatiqy\Slimlibs\Support\Helper\CodeOut;
use Albatiqy\Slimlibs\Support\Util\DocBlock;

/**
 * Mapping kelas Slimlibs Cli
 *
 * @arg [bin|required] nama perintah
 * @arg [class|required] kelas target
 */

final class CommandMapper extends AbstractCommand {

    public function main() { // validation
        $reflect = new \ReflectionClass('\\App\\Commands\\' . $this->args[1]);
        $result = $this->parseClass($reflect);
        $fileout = "<?php\nreturn [\n    \"handler\" => " . $reflect->getName() . "::class,\n    \"options\" => " . CodeOut::fromArray($result) . "\n];";
        \file_put_contents(\APP_DIR . '/var/commands/' . $this->args[0] . '.php', $fileout);
        $this->success("OK");
    }

    private function parseClass($reflect) {
        $result = [];
        $doc_block = new DocBlock($reflect);
        $result['help'] = $doc_block->getComment();
        $result['args'] = [];

        $args = $doc_block->getTag('arg', null, true);
        foreach ($args as $arg) {
            \preg_match('/^\[.*?\]/', $arg, $mtch);
            if (\count($mtch) > 0) {
                $conf = \substr($mtch[0], 1, -1);
                $conf = \explode('|', $conf);
                $help = \trim(\substr($arg, \strlen($mtch[0])));
                $arg = \trim($conf[0]);
                $required = false;
                if (\count($conf) > 1) {
                    $req = \trim($conf[1]);
                    $required = ($req == 'required' ? true : false);
                }
                $result['args'][] = [
                    'arg' => $arg,
                    'help' => $help,
                    'required' => $required,
                ];
            }
        }

        $result['opts'] = [];

        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $doc_block = new DocBlock($property);
            $pname = $property->getName();
            $pnamez = '';

            $opt = $doc_block->getTag('opt');
            $topt = [];
            if ($opt != null) {
                \preg_match('/^\[.*?\]/', $opt, $mtch);
                if (\count($mtch) > 0) {
                    $conf = \substr($mtch[0], 1, -1);
                    $conf = \explode('|', $conf);
                    $help = \trim(\substr($opt, \strlen($mtch[0])));
                    $pnamez = \trim($conf[0]);
                    $short = \trim($conf[1]);
                    $arg = false;
                    if (\count($conf) > 2) {
                        $noarg = \trim($conf[2]);
                        $arg = ($noarg == '-' ? false : $noarg);
                    }
                    $topt = [
                        'name' => $pname,
                        'help' => $help,
                        'short' => $short,
                        'arg' => $arg,
                    ];
                }
            }
            if (\count($topt) > 0) {
                if ($topt['arg'] === false && !$property->isDefault()) {
                    throw new \Exception('Property ' . $reflect->getName() . '::$' . $topt['name'] . ' require default value');
                }

                $result['opts'][$pnamez] = $topt;
            }
        }

        $result['commands'] = [];
        $methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $mname = $method->getName();
            $command = $mname;
            $cparameters = $method->getParameters();
            $parameters = [];
            foreach ($cparameters as $oparam) {
                $parameters[$oparam->getName()] = $oparam;
            }
            if ($mname != 'main') {
                $cmd = [];
                $doc_block = new DocBlock($method);
                $cmd['help'] = $doc_block->getComment();
                $alias = $doc_block->getTag('alias');
                $usealias = false;
                if ($alias != null) {
                    \preg_match('/^\[.*?\]/', $alias, $mtch);
                    if (\count($mtch) > 0) {
                        $command = \trim(\substr($mtch[0], 1, -1));
                        $usealias = true;
                    }
                }
                $cmd['name'] = $mname;
                $cmd['opts'] = [];
                $cmd['args'] = [];
                $copts = [];
                $opts = $doc_block->getTag('opt', null, true);
                foreach ($opts as $opt) {
                    \preg_match('/^\[.*?\]/', $opt, $mtch);
                    if (\count($mtch) > 0) {
                        $conf = \substr($mtch[0], 1, -1);
                        $conf = \explode('|', $conf);
                        $help = \trim(\substr($opt, \strlen($mtch[0])));
                        $varname = \substr(\trim($conf[0]), 1); //strip $
                        $long = \trim($conf[1]);
                        $short = \trim($conf[2]);
                        $arg = false;
                        if (\count($conf) > 3) {
                            $noarg = \trim($conf[3]);
                            $arg = ($noarg == '-' ? false : $noarg);
                        }
                        $copts[$long] = [
                            'name' => $varname,
                            'help' => $help,
                            'short' => $short,
                            'arg' => $arg,
                        ];
                    }
                }
                $cargs = [];
                $args = $doc_block->getTag('arg', null, true);
                foreach ($args as $arg) {
                    \preg_match('/^\[.*?\]/', $arg, $mtch);
                    if (\count($mtch) > 0) {
                        $conf = \substr($mtch[0], 1, -1);
                        $conf = \explode('|', $conf);
                        $help = \trim(\substr($arg, \strlen($mtch[0])));
                        $arg = \trim($conf[0]);
                        $required = false;
                        if (\count($conf) > 1) {
                            $req = \trim($conf[1]);
                            $required = ($req == 'required' ? true : false);
                        }
                        $carg = [
                            'arg' => $arg,
                            'help' => $help,
                            'required' => $required,
                        ];
                        $cargs[$arg] = $carg;
                        $cmd['args'][] = $carg;
                    }
                }
                foreach ($copts as $key => $copt) {
                    if (!\array_key_exists($copt['name'], $parameters)) {
                        throw new \Exception('Argument $' . $copt['name'] . ' not found in ' . $reflect->getName() . '::' . $cmd['name']);
                    }
                    if ($copt['arg'] === false && !$parameters[$copt['name']]->isDefaultValueAvailable()) {
                        throw new \Exception('Argument $' . $copt['name'] . ' in method ' . $reflect->getName() . '::' . $cmd['name'] . ' require default value');
                    }
                    $cmd['opts'][$key] = $copt;
                }
                if ((\count($cmd['opts']) > 0) || (\count($cargs) > 0) || $usealias) {
                    $result['commands'][$command] = $cmd;
                }
                unset($cargs);
            }
        }
        return $result;
    }
}
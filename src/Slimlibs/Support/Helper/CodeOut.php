<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class CodeOut {

    public static function fromArray($array,$indent='        ') { // if index numeric ??
        if (\is_array($array)) {
            $buff = '[';
            $i=1;
            $ct=count($array);
            foreach ($array as $key=>$value) {
                $buff .= "\n$indent".(\is_string($key)?"\"".addslashes($key)."\" => ":"");
                $buff .= self::fromArray($value,$indent."    ").($i<$ct?',':'');
                $i++;
            }
            $buff .= "\n$indent]";
            return $buff;
        } else {
            switch (\gettype($array)) {
                case 'boolean':
                    return ($array?'true':'false');
                case 'numeric':
                    return $array;
                case 'NULL':
                    return 'null';
                default:
                    return '"'.\addslashes((string)$array).'"';
            }
        }
    }
}
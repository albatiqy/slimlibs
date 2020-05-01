<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

class CommandException extends \RuntimeException {
    
    const E_ANY = -1;
    const E_UNKNOWN_OPT = 1;
    const E_OPT_ARG_REQUIRED = 2;
    const E_OPT_ARG_DENIED = 3;
    const E_OPT_ABIGUOUS = 4;

    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        if (!$code) {
            $code = self::E_ANY;
        }
        parent::__construct($message, $code, $previous);
    }
}
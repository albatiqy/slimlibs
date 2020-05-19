<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Database;

final class DbServiceException extends \Exception {

    const E_ANY = -1;
    const E_NO_RESULT = 1;
    const E_PDO = 0;
    const E_CLIENT = 9;

    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        if (!$code) {
            $code = self::E_ANY;
        }
        parent::__construct($message, $code, $previous);
    }
}